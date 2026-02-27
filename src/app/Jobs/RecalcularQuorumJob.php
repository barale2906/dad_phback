<?php

namespace App\Jobs;

use App\Events\QuorumUpdated;
use App\Models\Reunion;
use App\Services\QuorumService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalcularQuorumJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reunionId)
    {
    }

    public function handle(QuorumService $service): void
    {
        $reunion = Reunion::query()->find($this->reunionId);

        if (! $reunion) {
            return;
        }

        $quorum = $service->calcularQuorum($reunion);

        QuorumUpdated::dispatch(
            $reunion->id,
            $quorum['total_unidades'],
            $quorum['unidades_presentes'],
            $quorum['total_coeficiente'],
            $quorum['coeficiente_presente'],
            $quorum['porcentaje_unidades'],
            $quorum['porcentaje_coeficiente']
        );
    }
}
