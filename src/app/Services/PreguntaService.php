<?php

namespace App\Services;

use App\Models\Pregunta;
use RuntimeException;

class PreguntaService
{
    public function abrir(Pregunta $pregunta): void
    {
        if ($pregunta->estado === 'abierta') {
            throw new RuntimeException('La pregunta ya esta abierta.');
        }

        if ($pregunta->estado === 'cerrada' || $pregunta->estado === 'cancelada') {
            throw new RuntimeException('No se puede abrir una pregunta cerrada o cancelada.');
        }

        $existsOpen = Pregunta::query()
            ->where('reunion_id', $pregunta->reunion_id)
            ->where('estado', 'abierta')
            ->where('id', '!=', $pregunta->id)
            ->exists();

        if ($existsOpen) {
            throw new RuntimeException('Ya existe una pregunta abierta en la reunion.');
        }

        $pregunta->update([
            'estado' => 'abierta',
            'apertura_at' => now(),
        ]);
    }

    public function cerrar(Pregunta $pregunta): void
    {
        if ($pregunta->estado !== 'abierta') {
            throw new RuntimeException('Solo se puede cerrar una pregunta abierta.');
        }

        $pregunta->update([
            'estado' => 'cerrada',
            'cierre_at' => now(),
        ]);
    }
}
