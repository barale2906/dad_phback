<?php

namespace App\Services;

use App\Models\Reunion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReunionService
{
    public function __construct(private readonly QuorumService $quorumService)
    {
    }

    public function create(array $data): Reunion
    {
        return DB::transaction(function () use ($data): Reunion {
            $reunion = Reunion::query()->create([
                'tipo' => $data['tipo'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'modalidad' => $data['modalidad'],
                'ente' => $data['ente'],
                'estado' => $data['estado'] ?? 'programada',
            ]);

            if (! empty($data['zona_comun_ids'])) {
                $reunion->zonasComunes()->sync($data['zona_comun_ids']);
            }

            return $reunion->load('zonasComunes');
        });
    }

    public function update(Reunion $reunion, array $data): Reunion
    {
        return DB::transaction(function () use ($reunion, $data): Reunion {
            $reunion->update([
                'tipo' => $data['tipo'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'modalidad' => $data['modalidad'],
                'ente' => $data['ente'],
                'estado' => $data['estado'],
            ]);

            if (array_key_exists('zona_comun_ids', $data)) {
                $reunion->zonasComunes()->sync($data['zona_comun_ids'] ?? []);
            }

            return $reunion->load('zonasComunes');
        });
    }

    public function iniciar(Reunion $reunion): Reunion
    {
        if ($reunion->estado === 'en_curso') {
            throw new RuntimeException('La reunion ya se encuentra en curso.');
        }

        if ($reunion->estado === 'finalizada') {
            throw new RuntimeException('No se puede iniciar una reunion finalizada.');
        }


        $reunion->update([
            'estado' => 'en_curso',
            'inicio_at' => now(),
        ]);

        $yaExisteQuorum = $reunion->preguntas()
            ->where('tipo', 'QUORUM_CHECK')
            ->exists();

        if (! $yaExisteQuorum) {
            $this->quorumService->crearPreguntaQuorum($reunion);
        }

        return $reunion->fresh(['zonasComunes', 'convocatoria']);
    }

    public function cerrar(Reunion $reunion): Reunion
    {
        if ($reunion->estado === 'finalizada') {
            throw new RuntimeException('La reunion ya se encuentra finalizada.');
        }

        $reunion->update([
            'estado' => 'finalizada',
            'cierre_at' => now(),
        ]);

        return $reunion->fresh(['zonasComunes', 'convocatoria']);
    }
}
