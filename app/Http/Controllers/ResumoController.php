<?php

namespace App\Http\Controllers;

use App\Jobs\TranscreverJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResumoController extends Controller
{
    public function index() : View
    {
        return view('aplicacao.index');
    }

    public function resumir(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'video_url' => ['required', 'url'],
        ]);

        $async = TranscreverJob::dispatchWithConfig(
            url: $data['video_url'],
            model: 'base',
            lang: null,
            email: $data['email'],
        );

        $message = $async
            ? 'Solicitação enviada com sucesso! Você receberá o resumo por email em alguns minutos.'
            : 'Resumo gerado com sucesso! Confira seu email; ele deve chegar em instantes.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
            ], $async ? 202 : 200);
        }

        return redirect()
            ->route('index')
            ->with('status', $message);
    }
}
