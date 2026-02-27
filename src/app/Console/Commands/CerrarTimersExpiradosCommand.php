<?php

namespace App\Console\Commands;

use App\Services\TimerService;
use Illuminate\Console\Command;

class CerrarTimersExpiradosCommand extends Command
{
    protected $signature = 'timers:cerrar-expirados';

    protected $description = 'Cierra timers activos que ya expiraron por tiempo.';

    public function handle(TimerService $timerService): int
    {
        $updated = $timerService->cerrarExpirados();
        $this->info("Timers finalizados: {$updated}");

        return self::SUCCESS;
    }
}
