<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaService
{
    public function __construct(
        private string $baseUrl = '',
        private string $model = '',
    ) {
        $this->baseUrl = rtrim(config('services.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $this->model   = config('services.ollama.model', 'llama3.1:8b-instruct');
    }

    /**
     * Mantém a mesma assinatura, mas agora:
     * - quebra texto em chunks grandes (padrão ~12k chars);
     * - resume cada chunk;
     * - une todos em um único markdown;
     * - usa prompt default do config se existir, senão um fallback interno.
     */
    public function formatMarkdown(string $texto, ?string $prompt = null): string
    {
        $numCtx     = (int) config('services.ollama.num_ctx', 8192);
        $temperature= 0.2;

        // Prompts: usa do config, senão fallback interno simples
        $promptChunk = config('services.ollama.prompt_chunk') ??
            "Resuma objetivamente o trecho abaixo em bullet points claros (máx. 10). Mantenha nomes, datas e números. Trecho:\n";
        $promptMerge = config('services.ollama.prompt_merge') ??
            "Una os resumos parciais abaixo em um único texto Markdown organizado (Resumo executivo, Tópicos-chave, Perguntas, Próximos passos). Evite repetição. Resumos:\n";

        // Se o caller passou um prompt explícito, ele vira o prompt de MERGE final
        if ($prompt) {
            $promptMerge = rtrim($prompt)."\n\nResumos:\n";
        }

        // 1) chunking simples por caracteres (~12k) para não estourar contexto
        $chunks = $this->chunkUtf8($texto, 12000);
        $total  = max(1, count($chunks));

        // 2) MAP: resume cada chunk
        $partials = [];
        foreach ($chunks as $i => $chunk) {
            logger()->info("[ollama][map] processando chunk ".($i+1)."/{$total}");
            $partials[] = $this->ask($promptChunk.$chunk, $numCtx, $temperature, "[ollama][map][".($i+1)."/{$total}]");
        }

        // 3) REDUCE: merge final
        logger()->info("[ollama][reduce] unindo {$total} resumos parciais");
        $merged = $this->ask(
            $promptMerge . "\n\n---\n\n" . implode("\n\n---\n\n", $partials),
            $numCtx,
            $temperature,
            "[ollama][reduce]"
        );

        $final = trim($merged);
        if ($final === '') {
            throw new RuntimeException('Ollama retornou resposta vazia no merge.');
        }

        return $final;
    }

    private function ask(string $prompt, int $numCtx, float $temperature, string $tag): string
    {
        $payload = [
            'model'   => $this->model,
            'prompt'  => $prompt,
            'stream'  => false,
            'options' => [
                'num_ctx'     => $numCtx,
                'temperature' => $temperature,
            ],
        ];

        $resp = Http::timeout(1200)->post($this->baseUrl.'/api/generate', $payload);
        if ($resp->failed()) {
            logger()->error("$tag HTTP fail", ['status' => $resp->status(), 'body' => $resp->body()]);
            $resp->throw();
        }

        $text = (string) data_get($resp->json(), 'response', '');
        if ($text === '') {
            logger()->warning("$tag resposta vazia");
            throw new RuntimeException('Resposta vazia do Ollama.');
        }
        return $text;
    }

    private function chunkUtf8(string $s, int $max): array
    {
        $len = mb_strlen($s, 'UTF-8');
        if ($len <= $max) return [$s];

        $parts = [];
        for ($i = 0; $i < $len; $i += $max) {
            $parts[] = mb_substr($s, $i, $max, 'UTF-8');
        }
        return $parts;
    }
}

