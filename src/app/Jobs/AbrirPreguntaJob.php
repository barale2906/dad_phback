<?php

namespace App\Jobs;

use App\Models\Pregunta;
use App\Services\PreguntaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AbrirPreguntaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $preguntaId)
    {
    }

    public function handle(PreguntaService $preguntaService): void
    {
        $pregunta = Pregunta::query()->find($this->preguntaId);
        if (! $pregunta) {
            return;
        }

        $preguntaService->abrir($pregunta);
    }
}
