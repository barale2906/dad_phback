<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class WhatsAppMessageService
{
    public function extractMessages(array $payload): array
    {
        $messages = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    $from = (string) ($message['from'] ?? '');
                    $id = (string) ($message['id'] ?? '');
                    $text = $this->extractText($message);

                    if ($id !== '' && $from !== '') {
                        $messages[] = [
                            'message_id' => $id,
                            'phone' => $this->normalizePhone($from),
                            'text' => $text,
                            'timestamp' => $message['timestamp'] ?? null,
                        ];
                    }
                }
            }
        }

        return $messages;
    }

    public function extractText(array $message): string
    {
        if (($message['type'] ?? '') === 'text') {
            return trim((string) ($message['text']['body'] ?? ''));
        }

        return '';
    }

    public function normalizePhone(string $raw): string
    {
        return preg_replace('/\D/', '', $raw);
    }

    public function isReplay(string $messageId): bool
    {
        $key = 'whatsapp:message:'.$messageId;
        $exists = Cache::has($key);
        if (! $exists) {
            Cache::put($key, 1, now()->addDay());
        }

        return $exists;
    }

    public function checkRateLimit(string $phone): bool
    {
        $limit = config('whatsapp.rate_limit_per_minute', 10);
        $key = 'whatsapp:ratelimit:'.$phone.':'.now()->format('YmdHi');

        try {
            $count = (int) Redis::incr($key);
            Redis::expire($key, 90);

            return $count <= $limit;
        } catch (\Throwable) {
            return true;
        }
    }

    public function interpretCommand(string $text): ?string
    {
        $normalized = strtoupper(trim($text));

        foreach (config('whatsapp.comandos', []) as $comando => $variantes) {
            foreach ($variantes as $v) {
                if ($normalized === strtoupper($v) || $normalized === $v) {
                    return $comando;
                }
            }
        }

        return null;
    }
}
