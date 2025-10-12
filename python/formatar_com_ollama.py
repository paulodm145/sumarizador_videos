# file: formatar_com_ollama.py
import requests, json, os

OLLAMA_URL = os.getenv("OLLAMA_BASE_URL", "http://127.0.0.1:11434")
MODEL      = os.getenv("OLLAMA_MODEL", "mistral:7b-instruct
")

PROMPT_FMT = """Você é um redator instrucional. A partir do conteúdo abaixo, gere um e-mail educacional claro, objetivo e 100% em texto puro (sem Markdown e sem HTML).

                Regras e estrutura obrigatória:
                - Entregue exatamente dois blocos, usando estes marcadores:
                ===ASSUNTO===
                <título curto (máx. 90 caracteres) focado no tema principal, sem emojis>
                ===CORPO===
                <texto em parágrafos curtos, sem formatação especial>

                Conteúdo do CORPO (adicione apenas se houver informação no texto):
                1) Saudação breve e contextualização (1–2 linhas).
                2) Resumo executivo do vídeo (5–8 linhas), com foco pedagógico.
                3) Objetivos de aprendizagem (3–5 itens, em uma única linha por item, usando hífens “- ”).
                4) Pontos-chave do conteúdo (4–7 itens, “- ”).
                5) Mini-roteiro de aula sugerido (15–30 min): aquecimento, atividade principal, fechamento (3–5 linhas).
                6) Materiais/recursos necessários (se houver).
                7) Perguntas disparadoras para discussão (3–5 perguntas diretas).
                8) Sugestão de tarefa/atividade prática breve (2–3 linhas).
                9) Encerramento cordial + chamada para ação (ex.: “responder este e-mail com dúvidas”).

                Diretrizes:
                - Não invente fatos; se faltar algo, omita silenciosamente.
                - Use linguagem simples, inclusiva e direta.
                - Não use bullets especiais; apenas “- ” para listas.
                - Não inclua links, a menos que estejam no texto original.
                - Não gere assinatura; o sistema de e-mail adicionará.
                - Saída final apenas texto puro, seguindo rigorosamente os marcadores pedidos."""

def formatar_markdown(transcricao: str) -> str:
    payload = {
        "model": MODEL,
        "prompt": f"{PROMPT_FMT}\n\n---\n\nCONTEÚDO A EDITAR:\n{transcricao}",
        "stream": False,
        "options": {
            "num_ctx": int(os.getenv("OLLAMA_NUM_CTX", "8192")),
            "temperature": 0.2,
        }
    }
    r = requests.post(f"{OLLAMA_URL}/api/generate", json=payload, timeout=600)
    r.raise_for_status()
    data = r.json()
    return data.get("response", "").strip()

if __name__ == "__main__":
    # exemplo: ler transcrição gerada pelo whisper
    with open("/home/paulo/Desenvolvimento/sumarizador/python/runtime/transcricao_teste.txt", "r", encoding="utf-8") as f:
        raw = f.read()
    md = formatar_markdown(raw)
    out = "/home/paulo/Desenvolvimento/sumarizador/python/runtime/resumo_formatado.md"
    with open(out, "w", encoding="utf-8") as f:
        f.write(md)
    print("OK ->", out)
