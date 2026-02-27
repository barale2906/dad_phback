<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppMessageService $service
    ) {
    }

    /**
     * GET: Verificación del webhook por Meta (hub.mode, hub.verify_token, hub.challenge).
     */
    public function verify(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        if (! config('whatsapp.enabled')) {
            return response()->json(['error' => 'Webhook no habilitado'], 503);
        }

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe' || $token !== config('whatsapp.verify_token')) {
            Log::warning('WhatsApp webhook: verificación fallida', [
                'mode' => $mode,
                'token_match' => $token === config('whatsapp.verify_token'),
            ]);

            return response()->json(['error' => 'Invalid verification'], 403);
        }

        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * POST: Recepción de mensajes de WhatsApp (valida firma X-Hub-Signature-256).
     */
    public function receive(Request $request): JsonResponse
    {
        if (! config('whatsapp.enabled')) {
            return response()->json(['error' => 'Webhook no habilitado'], 503);
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature || ! str_starts_with($signature, 'sha256=')) {
            return response()->json(['error' => 'Firma no válida'], 403);
        }

        $payload = $request->getContent();
        $expected = 'sha256='.hash_hmac('sha256', $payload, config('whatsapp.app_secret'));
        if (! hash_equals($expected, $signature)) {
            Log::warning('WhatsApp webhook: firma HMAC inválida');
            return response()->json(['error' => 'Firma no válida'], 403);
        }

        $body = $request->all();
        if (empty($body['entry'])) {
            return response()->json(['message' => 'ok']);
        }

        $messages = $this->service->extractMessages($body);
        foreach ($messages as $msg) {
            ProcessWhatsAppMessageJob::dispatch($msg['message_id'], $msg['phone'], $msg);
        }

        return response()->json(['message' => 'ok']);
    }
}
