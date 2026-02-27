<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class BarcodeService
{
    public function print(int $inicio, int $cantidad, int $repeticiones): Response
    {
        $items = [];
        $actual = $inicio;

        for ($i = 0; $i < $cantidad; $i++) {
            for ($j = 0; $j < $repeticiones; $j++) {
                $items[] = $actual;
            }
            $actual++;
        }

        $html = view('pdf.barcodes', [
            'items' => $items,
            'inicio' => $inicio,
            'cantidad' => $cantidad,
            'repeticiones' => $repeticiones,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="barcodes.pdf"',
        ]);
    }
}
