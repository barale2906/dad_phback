<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Webhook Feature Flag
    |--------------------------------------------------------------------------
    |
    | Habilita o deshabilita la recepción de webhooks de Meta WhatsApp.
    |
    */

    'enabled' => env('WHATSAPP_WEBHOOK_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Verify Token (Meta App Dashboard)
    |--------------------------------------------------------------------------
    |
    | Token que configuras en el App Dashboard de Meta para la verificación
    | del webhook. El GET de verificación debe recibir este valor en
    | hub.verify_token.
    |
    */

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | App Secret
    |--------------------------------------------------------------------------
    |
    | App Secret de la app de Meta. Se usa para validar la firma
    | X-Hub-Signature-256 en los POST del webhook.
    |
    */

    'app_secret' => env('WHATSAPP_APP_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | Máximo de mensajes procesados por teléfono por minuto.
    |
    */

    'rate_limit_per_minute' => (int) env('WHATSAPP_RATE_LIMIT_PER_MINUTE', 10),

    /*
    |--------------------------------------------------------------------------
    | Comandos reconocidos
    |--------------------------------------------------------------------------
    |
    | Comandos de voto/presencia que el sistema interpreta desde WhatsApp.
    | Se comparan sin importar mayúsculas ni espacios.
    |
    */

    'comandos' => [
        'presente' => ['PRESENTE', 'ASISTO', 'A'],
        'si' => ['SI', 'SÍ', 'S', 'YES', 'Y'],
        'no' => ['NO', 'N'],
    ],

];
