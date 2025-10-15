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

        dispatch(new TranscreverJob($url, $model, $lang, $email));
        logger()->info("[command] Job enfileirado para transcrição do vídeo", [
            'url' => $url,
            'model' => $model,
            'lang' => $lang,
            'email' => $email,
        ]);
        $this->info("✅ Job enfileirado para o vídeo: {$url}");
        $this->info("Agora rode o worker: php artisan queue:work --timeout=0");
    }
}
