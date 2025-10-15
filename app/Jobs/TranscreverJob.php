<?php

namespace App\Jobs;

use App\Mail\ResultadoResumoMail;
use App\Services\TranscreverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class TranscreverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $model = 'base',
        public ?string $lang = null,
        public ?string $email = null
    ) {}

    public function handle(TranscreverService $service, \App\Services\OpenAiService $openAi): void
    {
        logger()->info('[job] Iniciando transcrição', ['url' => $this->url]);

        $result = $service->run($this->url, $this->model, $this->lang);
        $outFile = $result['output_file'] ?? null;
        logger()->info('[job] Transcrição concluída', ['arquivo' => $outFile]);

        $texto = file_get_contents($outFile);

        // Início do resumo (progressão via logs)
        logger()->info('[job] Resumo: iniciando (OpenAI - formatMarkdown)');
        $markdown = $openAi->formatMarkdown($texto);
        logger()->info('[job] Resumo: finalizado');

        // salvar em arquivo
        $dest = storage_path('app/resumos/'.basename($outFile, '.txt').'.md');
        @mkdir(dirname($dest), 0775, true);
        file_put_contents($dest, $markdown);

        logger()->info('[job] Resumo gravado', ['dest' => $dest]);

        $recipient = $this->email ?: config('services.resumo.notification_email');

        if (! empty($recipient)) {
            Mail::to($recipient)->send(new ResultadoResumoMail(
                urlVideo: $this->url,
                resumo: $markdown,
            ));

            logger()->info('[job] Resumo enviado por e-mail', ['destinatario' => $recipient]);
        } else {
            logger()->warning('[job] Resumo não enviado por e-mail: destinatário não configurado');
        }
    }

}
