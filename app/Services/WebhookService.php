<?php

namespace App\Services;

use App\Models\WebhookConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]);
    }

    public function dispatch(string $event, array $payload): void
    {
        $configs = WebhookConfig::where('active', true)->get();

        foreach ($configs as $config) {
            if (!in_array($event, $config->events ?? [])) {
                continue;
            }

            try {
                $body = json_encode(array_merge(['event' => $event], $payload));
                $headers = [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $event,
                ];

                if ($config->secret) {
                    $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $config->secret);
                }

                $this->client->post($config->url, [
                    'headers' => $headers,
                    'body' => $body,
                ]);
            } catch (RequestException $e) {
                Log::warning("Webhook failed [{$event}] to {$config->url}: " . $e->getMessage());
            }
        }
    }
}
