<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'resumo' => [
        'notification_email' => env('RESUMO_NOTIFICATION_EMAIL'),
        'dispatch_async' => env('RESUMO_DISPATCH_ASYNC', false),
    ],

    'transcribe' => [
        'venv'    => env('TRANSCRIBE_VENV'),
        'script'  => env('TRANSCRIBE_SCRIPT'),
        'timeout' => (int) env('TRANSCRIBE_TIMEOUT', 600),
        'workdir' => env('TRANSCRIBE_WORKDIR', storage_path('app/transcribe')),
    ],

    'ollama' => [
        'base_url'      => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model'         => env('OLLAMA_MODEL', 'llama3.1:8b-instruct'),
        'num_ctx'       => (int) env('OLLAMA_NUM_CTX', 8192),
        'prompt_default'=> <<<'PROMPT'
            Responder no Idioma Português (Brasil).
            Você é um editor sênior. Formate o conteúdo abaixo em Markdown objetivo, em PT-BR, com as seções:
                1. **Resumo executivo** (5–8 linhas)
                2. **Tópicos-chave** (bullet points concisos)
                3. **Linha do tempo** (se houver timestamps no texto, use mm:ss ou hh:mm:ss)
                4. **Conceitos e termos** (glossário com definições curtas)
                5. **Perguntas frequentes (FAQ)** (3–6 perguntas)
                6. **Citações notáveis** (até 5 frases curtas)
                7. **Próximos passos** (itens acionáveis)

                Regras:
                - Seja fiel ao conteúdo; não invente.
                - Texto direto, sem floreios.
                - Se faltar info para uma seção, inclua “(não identificado)”.
                - Saída exclusivamente em Markdown.
                - Responder em Português (Brasil).
            PROMPT,
    ],

    'openai' => [
        'base_url'      => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key'       => env('OPENAI_API_KEY'),
        'model'         => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'temperature'   => env('OPENAI_TEMPERATURE', 0.2),
        'max_tokens'    => env('OPENAI_MAX_TOKENS', 2048),
        'timeout'       => env('OPENAI_TIMEOUT', 120),
        'retries'       => env('OPENAI_RETRIES', 3),
        'backoff_ms'    => env('OPENAI_BACKOFF_MS', 800),
        'chunk_chars'   => env('OPENAI_CHUNK_CHARS', 12000),

        'system_prompt' => env('OPENAI_SYSTEM_PROMPT', <<<'PROMPT'
            Responder no Idioma Português (Brasil).
            Você é um editor sênior. Formate o conteúdo abaixo em Markdown objetivo, em PT-BR, com as seções:
                1. **Resumo executivo** (5–8 linhas)
                2. **Tópicos-chave** (bullet points concisos)
                3. **Linha do tempo** (se houver timestamps no texto, use mm:ss ou hh:mm:ss)
                4. **Conceitos e termos** (glossário com definições curtas)
                5. **Perguntas frequentes (FAQ)** (3–6 perguntas)
                6. **Citações notáveis** (até 5 frases curtas)
                7. **Próximos passos** (itens acionáveis)

                Regras:
                - Seja fiel ao conteúdo; não invente.
                - Texto direto, sem floreios.
                - Se faltar info para uma seção, inclua “(não identificado)”.
                - Saída exclusivamente em Markdown.
                - Responder em Português (Brasil).
            PROMPT
        ),

        // prompts padrão (customizáveis)
        'prompt_chunk'  => env('OPENAI_PROMPT_CHUNK', "Resuma objetivamente o trecho abaixo em bullet points claros (máx. 10). Mantenha nomes, datas e números. Trecho:\n"),
        'prompt_merge'  => env('OPENAI_PROMPT_MERGE', "Una os resumos parciais abaixo em um único texto Markdown organizado (Resumo executivo, Tópicos-chave, Perguntas, Próximos passos). Evite repetição. Resumos:\n"),
    ],

];
