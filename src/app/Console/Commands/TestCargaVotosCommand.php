<?php

namespace App\Console\Commands;

use App\Jobs\RegistrarVotoJob;
use App\Models\Opcion;
use App\Models\Pregunta;
use Illuminate\Console\Command;

class TestCargaVotosCommand extends Command
{
    protected $signature = 'test:carga-votos
                            {--pregunta= : ID de pregunta abierta}
                            {--total=100 : Número total de votos a encolar}
                            {--inmuebles= : IDs de inmuebles separados por coma (se repiten si hay menos que total)}';

    protected $description = 'Encola votos para test de carga (≥1000/min). Usar con pregunta abierta e inmuebles válidos.';

    public function handle(): int
    {
        $preguntaId = (int) $this->option('pregunta');
        $total = (int) $this->option('total');
        $inmueblesIds = $this->option('inmuebles')
            ? array_map('intval', explode(',', $this->option('inmuebles')))
            : [];

        if ($preguntaId <= 0) {
            $this->error('Debe indicar --pregunta=ID con una pregunta abierta.');

            return self::FAILURE;
        }

        $pregunta = Pregunta::query()->find($preguntaId);

        if (! $pregunta) {
            $this->error('Pregunta no encontrada.');

            return self::FAILURE;
        }

        if ($pregunta->estado !== 'abierta') {
            $this->error('La pregunta debe estar abierta.');

            return self::FAILURE;
        }

        $opcion = $pregunta->opciones()->first();

        if (! $opcion) {
            $this->error('La pregunta no tiene opciones.');

            return self::FAILURE;
        }

        $inmuebles = $inmueblesIds;

        if (empty($inmuebles)) {
            $this->error('Debe indicar --inmuebles=1,2,3 con IDs de inmuebles activos.');

            return self::FAILURE;
        }

        $start = microtime(true);
        $enqueued = 0;

        for ($i = 0; $i < $total; $i++) {
            $inmuebleId = $inmuebles[$i % count($inmuebles)];

            RegistrarVotoJob::dispatch(
                $preguntaId,
                $opcion->id,
                $inmuebleId,
                null,
                null
            );
            $enqueued++;
        }

        $elapsed = microtime(true) - $start;
        $votosPorMin = $elapsed > 0 ? ($enqueued / $elapsed) * 60 : 0;

        $this->info("Encolados: {$enqueued} votos en ".round($elapsed, 2).' s');
        $this->info('Estimado: '.round($votosPorMin, 0).' votos/min (objetivo ≥1000)');

        return self::SUCCESS;
    }
}
