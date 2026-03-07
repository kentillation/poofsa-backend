<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentRepository
{
    protected string $baseUrl;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.paymongo.base_url'), '/');
        $this->secret  = config('services.paymongo.secret_key');
    }

    protected function client()
    {
        return Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBasicAuth($this->secret, '')
        ->retry(3, 500); // 3 tries, 500ms delay;
    }

    // Circuit Breaker
    protected function circuitOpen(): bool
    {
        return cache()->get('paymongo:circuit_open', false);
    }

    protected function tripCircuit(): void
    {
        cache()->put('paymongo:circuit_open', true, now()->addSeconds(30));
    }

    public function createPaymentIntent(int $amount, array $opts = []): array
    {
        try {
            if ($this->circuitOpen()) {
                return [
                    'ok' => false,
                    'status' => 503,
                    'body' => ['error' => 'PayMongo temporarily unavailable'],
                ];
            }

            $response = $this->client()->post("{$this->baseUrl}/v1/payment_intents", [
                'data' => [
                    'attributes' => [
                        'amount'                 => $amount,
                        'currency'               => 'PHP',
                        'capture_type'           => 'automatic',
                        'payment_method_allowed' => $opts['methods'] ?? ['gcash', 'paymaya', 'qrph'],
                        'description'            => $opts['description'] ?? null,
                        'statement_descriptor'   => 'Poofsa',
                        'metadata'               => $opts['metadata'] ?? null,
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('PayMongo create Payment Intent failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'amount' => $amount,
                ]);

                $this->tripCircuit(); // if repeated failures

                return [
                    'ok'     => false,
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ];
            }

            $data = $response->json('data');

            return [
                'ok'         => true,
                'id'         => $data['id'],
                'status'     => $data['attributes']['status'],
                'client_key' => $data['attributes']['client_key'],
                'amount'     => $data['attributes']['amount'],
                'data'       => $data,
            ];
        } catch (\Exception $e) {
            Log::error('PayMongo create Payment Intent exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok'     => false,
                'status' => 500,
                'body'   => ['error' => $e->getMessage()],
            ];
        }
    }

    public function attachQrph(string $intentId, array $billing): array
    {
        $response = $this->client()->post("{$this->baseUrl}/v1/payment_methods", [
            'data' => [
                'attributes' => [
                    'type' => 'qrph',
                    'billing' => $billing,
                ],
            ],
        ]);

        if ($response->failed()) {
            return [
                'ok'     => false,
                'status' => $response->status(),
                'body'   => $response->json(),
            ];
        }

        $attach = $this->client()->post(
            "{$this->baseUrl}/v1/payment_intents/{$intentId}/attach",
            [
                'data' => [
                    'attributes' => [
                        'payment_method' => $response->json('data.id'),
                        'return_url' => config('app.frontend_url'),
                    ],
                ],
            ]
        );

        if ($attach->failed()) {
            return $this->fail('QRPh attach failed', $attach);
        }

        return [
            'ok' => true,
            'qr_image' => $attach->json('data.attributes.next_action.code.image_url'),
        ];
    }

    protected function fail(string $message, $response): array
    {
        Log::error($message, [
            'status'   => $response->status(),
            'response' => $response->json(),
        ]);

        return [
            'ok'     => false,
            'status' => $response->status(),
            'body'   => $response->json(),
        ];
    }

}
