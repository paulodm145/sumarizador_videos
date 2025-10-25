<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TranscreverJob;

class TranscreverVideo extends Command
{
    protected $signature = 'app:transcrever {url} {--model=base} {--lang=} {--email=}';
    protected $description = 'Enfileira a transcrição e resumo de um vídeo do YouTube';

    public function handle(): void
    {
        $url = $this->argument('url');
        $model = $this->option('model') ?? 'base';
        $lang = $this->option('lang') ?: null;
        $email = $this->option('email') ?: config('services.resumo.notification_email');

        try {
            $async = TranscreverJob::dispatchWithConfig($url, $model, $lang, $email);

            logger()->info('[command] Solicitação de transcrição enviada', [
                'url' => $url,
                'model' => $model,
                'lang' => $lang,
                'email' => $email,
                'async' => $async,
            ]);

            if ($async) {
                $this->info("✅ Job enfileirado para o vídeo: {$url}");
                $this->info('Lembre-se de executar: php artisan queue:work --timeout=0');
            } else {
                $this->info("✅ Processamento concluído para o vídeo: {$url}");
            }
        } catch (\Throwable $exception) {
            logger()->error('[command] Falha ao processar transcrição', [
                'url' => $url,
                'model' => $model,
                'lang' => $lang,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            $this->error('❌ Falha ao processar o vídeo. Verifique os logs para mais detalhes.');

            throw $exception;
        }
    }
}
