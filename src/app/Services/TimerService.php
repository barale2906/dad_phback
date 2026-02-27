<?php

namespace App\Services;

use App\Events\TimerUpdated;
use App\Models\Timer;
use RuntimeException;

class TimerService
{
    public function iniciar(Timer $timer): void
    {
        if ($timer->estado === 'activo') {
            throw new RuntimeException('El timer ya esta activo.');
        }

        $existsActive = Timer::query()
            ->where('reunion_id', $timer->reunion_id)
            ->where('tipo', $timer->tipo)
            ->where('estado', 'activo')
            ->where('id', '!=', $timer->id)
            ->exists();

        if ($existsActive) {
            throw new RuntimeException('Ya existe un timer activo del mismo tipo para la reunion.');
        }

        $timer->update([
            'estado' => 'activo',
            'inicio_at' => now(),
            'fin_at' => now()->addSeconds($timer->duracion_segundos),
        ]);

        TimerUpdated::dispatch(
            $timer->reunion_id,
            $timer->id,
            'activo',
            $timer->inicio_at?->toIso8601String(),
            $timer->fin_at?->toIso8601String()
        );
    }

    public function pausar(Timer $timer): void
    {
        if ($timer->estado !== 'activo') {
            throw new RuntimeException('Solo se puede pausar un timer activo.');
        }

        $timer->update([
            'estado' => 'pausado',
        ]);

        TimerUpdated::dispatch($timer->reunion_id, $timer->id, 'pausado', null, null);
    }

    public function cerrarExpirados(): int
    {
        $timers = Timer::query()
            ->where('estado', 'activo')
            ->whereNotNull('fin_at')
            ->where('fin_at', '<=', now())
            ->get();

        foreach ($timers as $timer) {
            $timer->update(['estado' => 'finalizado']);
            TimerUpdated::dispatch($timer->reunion_id, $timer->id, 'finalizado', null, null);
        }

        return $timers->count();
    }
}
