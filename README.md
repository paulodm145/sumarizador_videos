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
php artisan app:transcrever "URL_VIDEO_YOUTUBE" --model=tiny --lang=pt
```  


# sumarizador_videos
