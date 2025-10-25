<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResumoProcessamentoFalhouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $urlVideo,
        public readonly string $titulo = 'Não foi possível gerar o resumo do vídeo',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.resumo_falhou',
            with: [
                'titulo' => $this->titulo,
                'urlVideo' => $this->urlVideo,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
