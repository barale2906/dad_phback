<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso para la respuesta de estadísticas completas de una reunión.
 *
 * Incluye orden del día con cumplimiento, asistencia (registrados y no registrados)
 * y todas las votaciones con resultados y detalle por inmueble.
 */
class ReunionEstadisticasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reunion_id' => $this->resource['reunion_id'],
            'orden_dia' => $this->resource['orden_dia'],
            'asistencia' => $this->resource['asistencia'],
            'votaciones' => $this->resource['votaciones'],
        ];
    }
}
