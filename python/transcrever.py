# file: transcrever.py
import sys, json, argparse, yt_dlp, whisper, os

def download_audio(video_url, outdir="workdir"):
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
    }
    print("Baixando áudio...", file=sys.stderr, flush=True)
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        ydl.download([video_url])
    return f'{outdir}/audio.mp3'

def transcribe_audio(audio_file, model_name="base", language=None):
    print(f"Iniciando transcrição com Whisper ({model_name})...", file=sys.stderr, flush=True)
    model = whisper.load_model(model_name)
    result = model.transcribe(audio_file, language=language)
    print("Transcrição concluída.", file=sys.stderr, flush=True)
    return result['text']

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", required=True, help="URL do vídeo do YouTube")
    parser.add_argument("--model", default="base", help="Modelo do Whisper (tiny|base|small|medium|large)")
    parser.add_argument("--lang", default=None, help="Forçar idioma (ex: 'pt')")
    parser.add_argument("--out", default="transcricao.txt", help="Arquivo de saída")
    args = parser.parse_args()

    audio_path = download_audio(args.url)
    text = transcribe_audio(audio_path, model_name=args.model, language=args.lang)

    # Salva arquivo
    with open(args.out, "w", encoding="utf-8") as f:
        f.write(text)

    # Última linha do STDOUT = JSON (contrato estável p/ Laravel)
    payload = {
        "ok": True,
        "url": args.url,
        "model": args.model,
        "lang": args.lang,
        "audio_path": audio_path,
        "output_file": os.path.abspath(args.out),
        "preview": text[:500],
    }
    print(json.dumps(payload, ensure_ascii=False))

if __name__ == "__main__":
    main()
