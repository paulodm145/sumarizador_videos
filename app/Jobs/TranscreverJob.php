<?php

namespace App\Jobs;

use App\Services\TranscreverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscreverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $model = 'base',
        public ?string $lang = null
    ) {}

    public function handle(TranscreverService $service): void
    {
        logger()->info('[job] Iniciando transcrição', ['url' => $this->url, 'model' => $this->model, 'lang' => $this->lang]);

        try {
            $result = $service->run($this->url, $this->model, $this->lang);
            $outFile = $result['output_file'] ?? null;
            logger()->info('[job] Transcrição concluída', ['arquivo' => $outFile]);

            // 1) Valida o arquivo de transcrição
            if (!$outFile || !file_exists($outFile)) {
                logger()->error('[job] Arquivo de transcrição inexistente', ['output_file' => $outFile, 'result' => $result]);
                return;
            }
            if (!is_readable($outFile)) {
                logger()->error('[job] Arquivo de transcrição sem permissão de leitura', ['output_file' => $outFile]);
                return;
            }

            // 2) Lê a transcrição e loga tamanho
            $texto = @file_get_contents($outFile);
            if ($texto === false) {
                logger()->error('[job] Falha ao ler transcrição', ['output_file' => $outFile]);
                return;
            }
            logger()->info('[job] Transcrição lida', ['bytes' => mb_strlen($texto, '8bit')]);

            // 3) Gera o resumo com o Ollama
            logger()->info('[job] Resumo: iniciando (Ollama - formatMarkdown)');
            $markdown = app(\App\Services\OllamaService::class)->formatMarkdown($texto);

            if (!is_string($markdown)) {
                logger()->error('[job] Resumo inválido: resposta não é string');
                return;
            }
            $markdown = trim($markdown);
            if ($markdown === '') {
                logger()->error('[job] Resumo vazio retornado pelo Ollama');
                return;
            }
            logger()->info('[job] Resumo: finalizado', ['chars' => mb_strlen($markdown, 'UTF-8')]);

            // 4) Garante diretório e grava arquivo .md (com checagem do retorno)
            $dir  = storage_path('app/resumos');
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                logger()->error('[job] Falha ao criar diretório de saída', ['dir' => $dir]);
                return;
            }

            $destBase = basename($outFile, '.txt'); // ex: transcricao_xxx
            $dest     = $dir . '/' . $destBase . '.md';

            // grava com arquivo temporário e rename (mais seguro)
            $tmp = $dest . '.part';
            $bytes = @file_put_contents($tmp, $markdown);
            if ($bytes === false) {
                logger()->error('[job] Falha ao gravar arquivo temporário', ['tmp' => $tmp]);
                return;
            }
            if (!@rename($tmp, $dest)) {
                logger()->error('[job] Falha ao renomear arquivo final', ['tmp' => $tmp, 'dest' => $dest]);
                return;
            }
            @chmod($dest, 0664);

            logger()->info('[transcribe][done]', [
                'url'   => $this->url,
                'out'   => $dest,
                'model' => $this->model,
                'lang'  => $this->lang,
            ]);
        } catch (\Throwable $e) {
            logger()->error('[job] Erro inesperado', [
                'msg'   => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
