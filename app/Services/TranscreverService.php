<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Str;
use RuntimeException;

class TranscreverService
{
    public function run(string $youtubeUrl, string $model = 'base', ?string $lang = null): array
    {
        $venv    = config('services.transcribe.venv');
        $script  = config('services.transcribe.script');
        $timeout = config('services.transcribe.timeout', 600);
        $workdir = config('services.transcribe.workdir');

        if (!is_file($script)) {
            throw new RuntimeException("Script Python não encontrado: {$script}");
        }
        if (!is_dir($workdir)) {
            @mkdir($workdir, 0775, true);
        }

        $outputFile = $workdir.'/'.'transcricao_'.Str::uuid()->toString().'.txt';
        $pythonBin  = $venv.'/bin/python';

        $cmd = [
            $pythonBin,
            $script,
            '--url', $youtubeUrl,
            '--model', $model,
            '--out', $outputFile,
        ];
        if (!empty($lang)) {
            $cmd[] = '--lang';
            $cmd[] = $lang;
        }

        $process = new Process($cmd, null, [
            'PATH' => $venv.'/bin:'.getenv('PATH'),
        ]);
        $process->setTimeout($timeout);

        // (Opcional) Capturar logs de STDERR em tempo real
        $process->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                logger()->info("[transcribe][stderr] ".$buffer);
            } else {
                logger()->info("[transcribe][stdout] ".$buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new RuntimeException("Falha no processo: ".$process->getErrorOutput());
        }

        // A última linha do STDOUT é JSON (contrato do script)
        $out = trim($process->getOutput());
        $lines = array_values(array_filter(array_map('trim', explode("\n", $out))));
        $last  = end($lines) ?: '{}';
        $decoded = json_decode($last, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON inválido do Python: ".json_last_error_msg());
        }
        return $decoded;
    }
}
