<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Barcode\PrintBarcodeRequest;
use App\Services\BarcodeService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class BarcodeController extends Controller
{
    public function __construct(private readonly BarcodeService $barcodeService)
    {
    }

    public function print(PrintBarcodeRequest $request): Response
    {
        Gate::authorize('create', \App\Models\Asistente::class);

        return $this->barcodeService->print(
            (int) $request->integer('inicio'),
            (int) $request->integer('cantidad'),
            (int) $request->integer('repeticiones')
        );
    }
}
