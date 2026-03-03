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

    public function attachPaymentMethod(string $intentId, string $type, array $billing): array
    {
        try {
            if ($type === 'qrph') {
                return $this->attachQrph($intentId, $billing);
            } else {
                return $this->attachWallet($intentId, $type, $billing);
            }
        } catch (\Throwable $e) {
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

    protected function attachWallet(string $intentId, string $type, array $billing): array
    {
        $response = $this->client()->post("{$this->baseUrl}/v1/payment_methods", [
            'data' => [
                'attributes' => [
                    'type'    => $type,
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

        $pmId = $response->json('data.id');

        $attach = $this->client()->post("{$this->baseUrl}/v1/payment_intents/{$intentId}/attach", [
            'data' => [
                'attributes' => [
                    'payment_method' => $pmId,
                    'return_url'     => config('app.frontend_url') . '/cashier',
                ],
            ],
        ]);

        if ($attach->failed()) {
            return [
                'ok'     => false,
                'status' => $attach->status(),
                'body'   => $attach->json(),
            ];
        }

        $data = $attach->json('data');

        return [
            'ok'           => true,
            'id'           => $pmId,
            'status'       => $data['attributes']['status'] ?? null,
            'redirect_url' => $data['attributes']['next_action']['redirect']['url'] ?? null,
            'qr_image'     => $data['attributes']['next_action']['display_qr_code'] ?? null,
        ];
    }

    public function monitorPaymentIntent(string $intentId, bool $fetchLatest = true): array
    {
        try {
            $status = $this->getPaymentIntentStatus($intentId);

            if (!$status['ok']) {
                return $status;
            }

            if ($fetchLatest) {
                $payments = $this->getPaymentsForIntent($intentId);

                $latestPayment = null;
                if ($payments['ok'] && count($payments['payments']) > 0) {
                    $latestPayment = $payments['payments'][0];
                }

                $status['latest_payment'] = $latestPayment;
                $status['payment_count'] = count($payments['payments'] ?? []);
            }

            return $status;
        } catch (\Exception $e) {
            Log::error('PayMongo monitorPaymentIntent exception', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 500,
                'body' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function getPaymentIntentStatus(string $intentId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/v1/payment_intents/{$intentId}");

            if ($response->failed()) {
                Log::error('PayMongo getPaymentIntentStatus failed', [
                    'intent_id' => $intentId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return [
                    'ok'     => false,
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ];
            }

            $data = $response->json('data');
            $attributes = $data['attributes'] ?? [];

            $payments = $this->getPaymentsForIntent($intentId);
            $latestPayment = $payments['ok'] ? ($payments['payments'][0] ?? null) : null;

            $isPaid = false;
            $paidAt = null;

            if ($latestPayment) {
                $paymentAttributes = $latestPayment['attributes'] ?? [];
                $isPaid = ($paymentAttributes['status'] ?? '') === 'paid';
                $paidAt = $paymentAttributes['paid_at'] ?? null;
            }

            $statusMap = [
                'awaiting_payment_method' => 'pending',
                'awaiting_next_action' => 'pending',
                'processing' => 'processing',
                'succeeded' => $isPaid ? 'succeeded' : 'processing',
                'canceled' => 'cancelled',
                'requires_payment_method' => 'failed',
                'requires_confirmation' => 'pending',
                'requires_action' => 'pending',
                'requires_capture' => 'processing',
            ];

            $mappedStatus = $statusMap[$attributes['status']] ?? 'unknown';

            return [
                'ok'             => true,
                'id'             => $data['id'],
                'status'         => $mappedStatus,
                'original_status' => $attributes['status'],
                'amount'         => $attributes['amount'],
                'currency'       => $attributes['currency'],
                'paid_at'        => $paidAt,
                'metadata'       => $attributes['metadata'] ?? [],
                'created_at'     => $attributes['created_at'] ?? null,
                'updated_at'     => $attributes['updated_at'] ?? null,
                'data'           => $data,
            ];
        } catch (\Exception $e) {
            Log::error('PayMongo getPaymentIntentStatus exception', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok'     => false,
                'status' => 500,
                'body'   => ['error' => $e->getMessage()],
            ];
        }
    }

    public function getPaymentsForIntent(string $intentId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/v1/payments", [
                'query' => [
                    'payment_intent_id' => $intentId,
                    'limit' => 10, // Get up to 10 payments
                ]
            ]);

            if ($response->failed()) {
                Log::warning('PayMongo getPaymentsForIntent failed', [
                    'intent_id' => $intentId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ];
            }

            $data = $response->json();

            return [
                'ok' => true,
                'payments' => $data['data'] ?? [],
                'has_more' => $data['has_more'] ?? false,
                'total' => count($data['data'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error('PayMongo getPaymentsForIntent exception', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 500,
                'body' => ['error' => $e->getMessage()],
            ];
        }
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

    protected function error(string $message): array
    {
        Log::error($message);

        return [
            'ok'     => false,
            'status' => 500,
            'body'   => ['error' => $message],
        ];
    }

    // public function getPayments(array $params = []): array
    // {
    //     try {
    //         $defaultParams = [
    //             'limit' => 20,
    //             'before' => null,
    //             'after' => null,
    //         ];

    //         $queryParams = array_merge($defaultParams, array_filter($params));

    //         $response = $this->client()->get("{$this->baseUrl}/v1/payments", [
    //             'query' => array_filter($queryParams, fn($value) => $value !== null)
    //         ]);

    //         if ($response->failed()) {
    //             Log::warning('PayMongo getPayments failed', [
    //                 'params' => $params,
    //                 'status' => $response->status(),
    //                 'response' => $response->json(),
    //             ]);

    //             return [
    //                 'ok' => false,
    //                 'status' => $response->status(),
    //                 'body' => $response->json(),
    //             ];
    //         }

    //         $data = $response->json();

    //         return [
    //             'ok' => true,
    //             'payments' => $data['data'] ?? [],
    //             'has_more' => $data['has_more'] ?? false,
    //             'before' => $data['data']['0']['id'] ?? null,
    //             'after' => $data['data'][count($data['data'] ?? []) - 1]['id'] ?? null,
    //             'total' => count($data['data'] ?? []),
    //         ];
    //     } catch (\Exception $e) {
    //         Log::error('PayMongo getPayments exception', [
    //             'params' => $params,
    //             'error' => $e->getMessage(),
    //         ]);

    //         return [
    //             'ok' => false,
    //             'status' => 500,
    //             'body' => ['error' => $e->getMessage()],
    //         ];
    //     }
    // }

    // protected function attachToIntent(string $intentId, string $paymentMethodId): array
    // {
    //     $response = $this->client()->post(
    //         "{$this->baseUrl}/v1/payment_intents/{$intentId}/attach",
    //         [
    //             'data' => [
    //                 'attributes' => [
    //                     'payment_method' => $paymentMethodId,
    //                     'return_url'     => config('app.frontend_url') . '/cashier',
    //                 ],
    //             ],
    //         ]
    //     );

    //     if ($response->failed()) {
    //         return $this->fail('Attach payment method failed', $response);
    //     }

    //     return [
    //         'ok'          => true,
    //         'data'        => $response->json('data'),
    //         'next_action' => $response->json('data.attributes.next_action'),
    //     ];
    // }

    // public function cancelPaymentIntent(string $intentId): array
    // {
    //     try {
    //         $response = $this->client()->post("{$this->baseUrl}/v1/payment_intents/{$intentId}/cancel");

    //         if ($response->failed()) {
    //             Log::error('PayMongo cancelPaymentIntent failed', [
    //                 'intent_id' => $intentId,
    //                 'status' => $response->status(),
    //                 'response' => $response->json(),
    //             ]);

    //             return [
    //                 'ok'     => false,
    //                 'status' => $response->status(),
    //                 'body'   => $response->json(),
    //             ];
    //         }

    //         $data = $response->json('data');

    //         return [
    //             'ok'     => true,
    //             'id'     => $data['id'],
    //             'status' => $data['attributes']['status'],
    //             'data'   => $data,
    //         ];
    //     } catch (\Exception $e) {
    //         Log::error('PayMongo cancelPaymentIntent exception', [
    //             'intent_id' => $intentId,
    //             'error' => $e->getMessage(),
    //         ]);

    //         return [
    //             'ok'     => false,
    //             'status' => 500,
    //             'body'   => ['error' => $e->getMessage()],
    //         ];
    //     }
    // }

    // public function isPaymentSuccessful(string $intentId): bool
    // {
    //     $status = $this->getPaymentIntentStatus($intentId);

    //     if (!$status['ok']) {
    //         return false;
    //     }

    //     $successStatuses = ['succeeded'];
    //     $failedStatuses = ['failed', 'cancelled', 'expired'];

    //     if (in_array($status['status'], $successStatuses)) {
    //         return true;
    //     }

    //     if (in_array($status['status'], $failedStatuses)) {
    //         return false;
    //     }

    //     $payments = $this->getPaymentsForIntent($intentId);
    //     if ($payments['ok'] && count($payments['payments']) > 0) {
    //         foreach ($payments['payments'] as $payment) {
    //             $paymentStatus = $payment['attributes']['status'] ?? '';
    //             if ($paymentStatus === 'paid') {
    //                 return true;
    //             }
    //         }
    //     }

    //     return false;
    // }
}
