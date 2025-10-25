# file: transcrever.py
import sys
import json
import argparse
import yt_dlp
from yt_dlp.utils import DownloadError
import whisper
import os
import logging


def setup_logging(log_dir="logs", log_file_name="transcrever.log"):
    os.makedirs(log_dir, exist_ok=True)
    log_file_path = os.path.join(log_dir, log_file_name)
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s [%(levelname)s] %(message)s",
        handlers=[
            logging.FileHandler(log_file_path, encoding="utf-8"),
            logging.StreamHandler(sys.stderr),
        ],
        force=True,
    )
    logging.info("Logger inicializado. Os logs serão gravados em %s", log_file_path)
    return log_file_path

def download_audio(video_url, outdir="workdir", cookies_file=None):
    os.makedirs(outdir, exist_ok=True)
    ydl_opts = {
        'format': 'bestaudio/best',
        'outtmpl': f'{outdir}/audio.%(ext)s',
        'postprocessors': [{
            'key': 'FFmpegExtractAudio',
            'preferredcodec': 'mp3',
            'preferredquality': '192',
        }],
        'quiet': True,
        'noprogress': True,
        'retries': 3,
        'http_headers': {
            'User-Agent': (
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '
                '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ),
            'Accept-Language': 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        },
        'noplaylist': True,
    }

    if cookies_file:
        if not os.path.exists(cookies_file):
            raise FileNotFoundError(f"Arquivo de cookies não encontrado: {cookies_file}")
        ydl_opts['cookiefile'] = cookies_file
        logging.info("Usando cookies para autenticar o download: %s", cookies_file)

    logging.info("Baixando áudio do vídeo: %s", video_url)
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            ydl.download([video_url])
    except DownloadError as err:
        logging.error("Falha ao baixar o áudio: %s", err)
        raise
    audio_path = f'{outdir}/audio.mp3'
    logging.info("Áudio salvo em %s", os.path.abspath(audio_path))
    return audio_path

def transcribe_audio(audio_file, model_name="base", language=None):
    logging.info(
        "Iniciando transcrição com Whisper (modelo=%s, idioma=%s) para o arquivo %s",
        model_name,
        language or "auto",
        audio_file,
    )
    model = whisper.load_model(model_name)
    result = model.transcribe(audio_file, language=language)
    logging.info("Transcrição concluída com sucesso.")
    return result['text']

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", required=True, help="URL do vídeo do YouTube")
    parser.add_argument("--model", default="base", help="Modelo do Whisper (tiny|base|small|medium|large)")
    parser.add_argument("--lang", default=None, help="Forçar idioma (ex: 'pt')")
    parser.add_argument("--out", default="transcricao.txt", help="Arquivo de saída")
    parser.add_argument(
        "--cookies-file",
        default=os.environ.get("TRANSCRIBE_COOKIES_FILE"),
        help=(
            "Arquivo de cookies exportado do navegador para contornar erros 403/410. "
            "Também pode ser definido pela variável de ambiente TRANSCRIBE_COOKIES_FILE."
        ),
    )
    args = parser.parse_args()

    log_file_path = setup_logging()
    audio_path = None

    logging.info(
        "Processo de transcrição iniciado para a URL %s com o modelo %s",
        args.url,
        args.model,
    )

    try:
        try:
            audio_path = download_audio(args.url, cookies_file=args.cookies_file)
        except DownloadError as err:
            error_message = str(err)
            if "HTTP Error 403" in error_message and not args.cookies_file:
                logging.warning(
                    "Download bloqueado com HTTP 403. Requisite cookies exportados do navegador."
                )
                error_message = (
                    "O YouTube retornou HTTP 403 (acesso negado). "
                    "Exporte os cookies do seu navegador e informe o caminho via "
                    "--cookies-file ou variável TRANSCRIBE_COOKIES_FILE."
                )
            raise RuntimeError(error_message) from err
        text = transcribe_audio(audio_path, model_name=args.model, language=args.lang)

        with open(args.out, "w", encoding="utf-8") as f:
            f.write(text)

        payload = {
            "ok": True,
            "url": args.url,
            "model": args.model,
            "lang": args.lang,
            "audio_path": audio_path,
            "output_file": os.path.abspath(args.out),
            "preview": text[:500],
            "log_file": os.path.abspath(log_file_path),
        }
        logging.info("Processo concluído. Transcrição salva em %s", payload["output_file"])
        print(json.dumps(payload, ensure_ascii=False))
    except Exception as exc:
        logging.exception("Falha ao processar a transcrição do vídeo")
        error_payload = {
            "ok": False,
            "url": args.url,
            "model": args.model,
            "lang": args.lang,
            "audio_path": audio_path,
            "output_file": os.path.abspath(args.out),
            "error": str(exc),
            "log_file": os.path.abspath(log_file_path),
        }
        print(json.dumps(error_payload, ensure_ascii=False))
        sys.exit(1)

if __name__ == "__main__":
    main()
