<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogSimulatePerformance
{
    private const CSV_PATH = 'logs/simulate-perf.csv';
    private const CSV_HEADERS = ['timestamp', 'phone', 'text', 'message_id', 'duration_ms', 'status_code', 'response_body'];

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $this->writeCsvRow([
            'timestamp'     => now()->toIso8601String(),
            'phone'         => $request->input('phone', ''),
            'text'          => $request->input('text', ''),
            'message_id'    => $request->input('message_id', ''),
            'duration_ms'   => $durationMs,
            'status_code'   => $response->getStatusCode(),
            'response_body' => $response->getContent(),
        ]);

        return $response;
    }

    private function writeCsvRow(array $row): void
    {
        $path = storage_path(self::CSV_PATH);

        $writeHeaders = ! file_exists($path);

        $handle = fopen($path, 'a');

        if ($handle === false) {
            return;
        }

        if ($writeHeaders) {
            fputcsv($handle, self::CSV_HEADERS);
        }

        fputcsv($handle, array_values($row));

        fclose($handle);
    }
}
