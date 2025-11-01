<x-mail::message>
# {{ $titulo }}

Não foi possível concluir o processamento do vídeo {{ $urlVideo }}.

Pedimos que tente novamente em alguns minutos. Se o problema persistir, responda a este e-mail para que possamos ajudar.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
