<x-mail::message>
# {{ $titulo }}

Resumo gerado automaticamente para o vídeo: {{ $urlVideo }}

**Resumo:**
{{ $resumo }}

<x-mail::button :url="$urlVideo">
    Abrir Vídeo
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
