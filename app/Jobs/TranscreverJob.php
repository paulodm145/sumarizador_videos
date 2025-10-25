<?php

namespace App\Jobs;

use App\Mail\ResultadoResumoMail;
use App\Mail\ResumoProcessamentoFalhouMail;
use App\Services\TranscreverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;
use function dispatch;
use function dispatch_sync;

class TranscreverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $model = 'base',
        public ?string $lang = null,
        public ?string $email = null
    ) {}

    public static function dispatchWithConfig(
        string $url,
        string $model = 'base',
        ?string $lang = null,
        ?string $email = null
    ): bool {
        $async = (bool) config('services.resumo.dispatch_async', false);
        $job = new self($url, $model, $lang, $email);

        if ($async) {
            dispatch($job);
        } else {
            dispatch_sync($job);
        }

        return $async;
    }

    public function handle(TranscreverService $service, \App\Services\OpenAiService $openAi): void
    {
        logger()->info('[job] Iniciando transcrição', ['url' => $this->url]);

        try {
            $result = $service->run($this->url, $this->model, $this->lang);
            $outFile = $result['output_file'] ?? null;
            logger()->info('[job] Transcrição concluída', ['arquivo' => $outFile]);

            if (empty($outFile) || !is_file($outFile)) {
                throw new RuntimeException('Arquivo de transcrição não encontrado.');
            }

            $texto = file_get_contents($outFile);

            if ($texto === false) {
                throw new RuntimeException('Não foi possível ler o arquivo de transcrição gerado.');
            }

            // Início do resumo (progressão via logs)
            logger()->info('[job] Resumo: iniciando (OpenAI - formatMarkdown)');
            $markdown = $openAi->formatMarkdown($texto);
            logger()->info('[job] Resumo: finalizado');

            // salvar em arquivo
            $dest = storage_path('app/resumos/'.basename($outFile, '.txt').'.md');
            @mkdir(dirname($dest), 0775, true);
            file_put_contents($dest, $markdown);

            logger()->info('[job] Resumo gravado', ['dest' => $dest]);

            $recipient = $this->resolveRecipient();
            logger()->info('[job] Destinatário do resumo', ['destinatario' => $recipient]);

            $this->notifySuccess($recipient, $markdown);
        } catch (Throwable $exception) {
            logger()->error('[job] Falha ao processar resumo', [
                'url' => $this->url,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $this->notifyFailure($exception);

            if (method_exists($this, 'fail')) {
                $this->fail($exception);
            }
        }
    }

    private function resolveRecipient(): string
    {
        return trim((string) ($this->email ?: config('services.resumo.notification_email')));
    }

    private function notifySuccess(string $recipient, string $markdown): void
    {
        if ($recipient === '') {
            logger()->warning('[job] Resumo não enviado por e-mail: destinatário não configurado');
            return;
        }

        try {
            logger()->info('[job] Preparando envio do resumo por e-mail', [
                'destinatario' => $recipient,
            ]);

            Mail::to($recipient)->send(new ResultadoResumoMail(
                urlVideo: $this->url,
                resumo: $markdown,
            ));

            logger()->info('[job] Resumo enviado por e-mail', ['destinatario' => $recipient]);
        } catch (Throwable $exception) {
            logger()->error('[job] Falha ao enviar resumo por e-mail', [
                'destinatario' => $recipient,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyFailure(Throwable $exception): void
    {
        $recipient = $this->resolveRecipient();

        if ($recipient === '') {
            logger()->warning('[job] Falha de processamento sem destinatário para notificação', [
                'url' => $this->url,
                'motivo' => $exception->getMessage(),
            ]);
            return;
        }

        try {
            Mail::to($recipient)->send(new ResumoProcessamentoFalhouMail(
                urlVideo: $this->url,
            ));

            logger()->info('[job] Notificação de falha enviada', [
                'destinatario' => $recipient,
                'url' => $this->url,
            ]);
        } catch (Throwable $mailException) {
            logger()->error('[job] Falha ao notificar usuário sobre erro', [
                'destinatario' => $recipient,
                'url' => $this->url,
                'message' => $mailException->getMessage(),
            ]);
        }
    }

}
