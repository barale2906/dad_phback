<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    /**
     * Métricas de observabilidad: colas, retries, duplicados evitados.
     */
    public function __invoke(): JsonResponse
    {
        $queues = ['votaciones', 'whatsapp', 'default'];
        $pending = [];

        try {
            if (config('queue.default') === 'redis') {
                $connection = Redis::connection(config('queue.connections.redis.connection', 'default'));

                foreach ($queues as $queue) {
                    $pending[$queue] = (int) $connection->llen('queues:'.$queue);
                }
            }
        } catch (\Throwable) {
            $pending = array_fill_keys($queues, 0);
        }

        return response()->json([
            'data' => [
                'queues' => ['pending' => $pending],
                'criterios_fase10' => [
                    'objetivo_votos_min' => 1000,
                    'objetivo_latencia_p95_ms' => 300,
                    'duplicados' => 'evitados por unique(pregunta_id,inmueble_id)',
                ],
            ],
        ]);
    }
}
