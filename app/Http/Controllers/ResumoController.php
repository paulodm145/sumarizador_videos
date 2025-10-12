<?php

namespace App\Http\Controllers;

use App\Services\OllamaService;
use Illuminate\Http\Request;

class ResumoController extends Controller
{
    public function __construct(private OllamaService $ollama) {}

    public function formatar(Request $req)
    {
        $data = $req->validate([
            'texto'  => ['required', 'string'],
            'prompt' => ['nullable', 'string'],
        ]);

        $md = $this->ollama->formatMarkdown($data['texto'], $data['prompt'] ?? null);
        return response()->json(['ok' => true, 'markdown' => $md]);
    }
}
