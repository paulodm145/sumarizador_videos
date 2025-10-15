<?php

namespace App\Http\Controllers;

use App\Services\TranscreverService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResumoController extends Controller
{
    public function __construct(private TranscreverService $transcrever) {}

    public function index() : View
    {
        return view('aplicacao.index');
    }

    public function resumir(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'url_video' => ['required', 'url'],
        ]);

    }
}
