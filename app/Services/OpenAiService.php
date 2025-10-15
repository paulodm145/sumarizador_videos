<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class OpenAiService
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;
    private float $temperature;
    private int $maxTokens;
    private string $systemPrompt;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiKey = null,
        ?string $model = null,
        ?float $temperature = null,
        ?int $maxTokens = null,
        ?string $systemPrompt = null,
    ) {
        $this->baseUrl      = rtrim($baseUrl ?? config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $this->apiKey       = $apiKey ?? (string) config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->model        = $model ?? (string) config('services.openai.model', 'gpt-4o-mini');
        $this->temperature  = $temperature ?? (float) config('services.openai.temperature', 0.2);
        $this->maxTokens    = $maxTokens ?? (int) config('services.openai.max_tokens', 2048);
        $this->systemPrompt = $systemPrompt ?? (string) config('services.openai.system_prompt', 'Você é um assistente que produz saídas claras, objetivas e em Markdown.');
    }

    /**
     * Mantém a mesma assinatura do seu OllamaService::formatMarkdown
     * - quebra texto em chunks (~12k chars);
     * - resume cada chunk (MAP);
     * - faz merge em um único markdown (REDUCE);
     * - aceita prompt custom de MERGE, com fallback em config.
     */
    public function formatMarkdown(string $texto, ?string $prompt = null): string
    {
        $promptChunk = config('services.openai.prompt_chunk') ??
            "Resuma objetivamente o trecho abaixo em bullet points claros (máx. 10). Mantenha nomes, datas e números. Trecho:\n";
        $promptMerge = config('services.openai.prompt_merge') ??
            "Una os resumos parciais abaixo em um único texto Markdown organizado (Resumo executivo, Tópicos-chave, Perguntas, Próximos passos). Evite repetição. Resumos:\n";

        if ($prompt) {
            $promptMerge = rtrim($prompt) . "\n\nResumos:\n";
        }

        $chunks = $this->chunkUtf8($texto, (int) config('services.openai.chunk_chars', 12000));
        $total  = max(1, count($chunks));

        // MAP
        $partials = [];
        foreach ($chunks as $i => $chunk) {
            $idx = $i + 1;
            logger()->info("[openai][map] processando chunk {$idx}/{$total}");
            $partials[] = $this->ask(
                prompt: $promptChunk . $chunk,
                tag: "[openai][map][{$idx}/{$total}]"
            );
        }

        // REDUCE
        logger()->info("[openai][reduce] unindo {$total} resumos parciais");
        $merged = $this->ask(
            prompt: $promptMerge . "\n\n---\n\n" . implode("\n\n---\n\n", $partials),
            tag: "[openai][reduce]"
        );

        $final = trim($merged);
        if ($final === '') {
            throw new RuntimeException('OpenAI retornou resposta vazia no merge.');
        }

        return $final;
    }

    /**
     * Chamada básica ao endpoint de chat completions (não-stream).
     * Implementa retries com backoff exponencial simples para 429/5xx.
     */
    private function ask(string $prompt, string $tag): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY não configurada.');
        }

        $payload = [
            'model'       => $this->model,
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
            'messages'    => [
                ['role' => 'system', 'content' => $this->systemPrompt],
                ['role' => 'user',   'content' => $prompt],
            ],
        ];

        $attempts = (int) config('services.openai.retries', 3);
        $backoff  = (int) config('services.openai.backoff_ms', 800);

        for ($try = 1; $try <= $attempts; $try++) {
            try {
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                    ->timeout((int) config('services.openai.timeout', 120))
                    ->post($this->baseUrl.'/chat/completions', $payload);

                if ($resp->status() === 429 || $resp->serverError()) {
                    throw new RequestException($resp);
                }

                if ($resp->failed()) {
                    logger()->error("$tag HTTP fail", ['status' => $resp->status(), 'body' => $resp->body()]);
                    $resp->throw();
                }

                $text = (string) data_get($resp->json(), 'choices.0.message.content', '');
                if ($text === '') {
                    logger()->warning("$tag resposta vazia");
                    throw new RuntimeException('Resposta vazia da OpenAI.');
                }

                return $text;
            } catch (\Throwable $e) {
                $isLast = $try === $attempts;
                logger()->warning("$tag tentativa {$try}/{$attempts} falhou", [
                    'error' => $e->getMessage(),
                ]);
                if ($isLast) {
                    throw new RuntimeException("Falha na chamada à OpenAI após {$attempts} tentativas: ".$e->getMessage(), (int)$e->getCode(), $e);
                }
                usleep($backoff * 1000);
                $backoff = (int) min($backoff * 2, 8000);
            }
        }

        // inatingível
        throw new RuntimeException('Falha desconhecida na chamada à OpenAI.');
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
