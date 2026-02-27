<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reunion_id' => $this->reunion_id,
            'tipo' => $this->tipo,
            'duracion_segundos' => $this->duracion_segundos,
            'inicio_at' => optional($this->inicio_at)?->toIso8601String(),
            'fin_at' => optional($this->fin_at)?->toIso8601String(),
            'estado' => $this->estado,
            'interviniente_nombre' => $this->interviniente_nombre,
            'interviniente_asistente_id' => $this->interviniente_asistente_id,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
