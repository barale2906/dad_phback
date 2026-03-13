<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSenderService
{
    public function sendText(string $to, string $message): void
    {
        if (! config('whatsapp.enabled')) {
            Log::info('[WA-Sender] Webhook deshabilitado — mensaje no enviado.', [
                'to' => $to,
                'body' => $message,
            ]);

            return;
        }

        $phoneNumberId = config('whatsapp.phone_number_id');
        $accessToken   = config('whatsapp.access_token');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::info('[WA-Sender] Credenciales no configuradas — mensaje simulado:', [
                'to'   => $to,
                'body' => $message,
            ]);

            return;
        }

        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if (! $response->successful()) {
            Log::warning('[WA-Sender] Error al enviar mensaje.', [
                'to'     => $to,
                'status' => $response->status(),
                'error'  => $response->json('error'),
            ]);
        }
    }
}
