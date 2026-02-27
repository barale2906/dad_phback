<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalSimulateMessageController extends Controller
{
    /**
     * POST: Simula un mensaje entrante de WhatsApp para pruebas (sin verificación de firma).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'text' => ['required', 'string', 'max:500'],
            'message_id' => ['nullable', 'string', 'max:100'],
        ]);

        $messageId = $request->input('message_id') ?? 'sim-'.uniqid().'-'.time();
        $phone = preg_replace('/\D/', '', $request->input('phone'));
        $payload = [
            'message_id' => $messageId,
            'phone' => $phone,
            'text' => trim($request->input('text')),
        ];

        ProcessWhatsAppMessageJob::dispatch($messageId, $phone, $payload);

        return response()->json([
            'message' => 'Mensaje encolado para procesamiento.',
            'message_id' => $messageId,
        ], 202);
    }
}
