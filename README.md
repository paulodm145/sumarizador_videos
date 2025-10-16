# Sumarizador de vídeos com IA
Este projeto é um sumarizador de vídeos que utiliza inteligência artificial para gerar resumos concisos e informativos a partir do conteúdo de vídeos. O objetivo é facilitar o consumo de informações, permitindo que os usuários obtenham rapidamente os pontos principais de um vídeo sem precisar assisti-lo na íntegra.        

TRANSCRIBE_VENV=/var/www/seu-projeto/venv-transcribe  
TRANSCRIBE_SCRIPT=/var/www/seu-projeto/transcrever.py  
TRANSCRIBE_TIMEOUT=600  
TRANSCRIBE_WORKDIR=/var/www/seu-projeto/runtime/transcribe  
---
# Utilização
## Via Command Line Interface (CLI)  
```bash
php artisan app:transcrever "URL_VIDEO_YOUTUBE" --model=tiny --lang=pt --email=destinatario@example.com
```

Se o parâmetro `--email` não for informado, o job utilizará o valor configurado na variável de ambiente `RESUMO_NOTIFICATION_EMAIL`.

Por padrão o processamento roda imediatamente após o envio (sem necessidade de worker) graças à configuração `RESUMO_DISPATCH_ASYNC=false`. Caso deseje enfileirar e processar com um worker dedicado, altere para `true` e execute `php artisan queue:work --timeout=0`.


# sumarizador_videos
