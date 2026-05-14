<?php

namespace App\Actions\Payment;

use App\Repositories\PaymentRepository;
use App\Models\PaymentModel;

class GenerateQrPaymentAction
{
    public function __construct(
        private PaymentRepository $paymongo
    ) {}

    public function execute(int $amount, string $referenceNumber): array
    {
        $idempotencyKey = hash('sha256', $referenceNumber);

        $existing = PaymentModel::where('idempotency_key', $idempotencyKey)->first();

        if ($existing && $existing->payment_intent_id) {
            return [
                'payment_intent_id' => $existing->payment_intent_id,
                'qr_image' => $existing->qr_image,
                'status' => $existing->status,
            ];
        }

        $amountInCentavos = $amount * 100;

        $intent = $this->paymongo->createPaymentIntent(
            $amountInCentavos,
            [
                'methods' => ['qrph'],
                'metadata' => [
                    'reference_number' => $referenceNumber,
                ],
            ]
        );

        abort_if(!$intent['ok'], 500, 'Payment intent failed');

        $qr = $this->paymongo->attachQrph($intent['id'], [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '09123456789',
        ]);

        abort_if(!$qr['ok'], 500, 'QR generation failed');

        PaymentModel::create([
            'idempotency_key' => $idempotencyKey,
            'payment_intent_id' => $intent['id'],
            'reference_number' => $referenceNumber,
            'amount' => $amountInCentavos,
            'status' => 'pending',
        ]);

        return [
            'payment_intent_id' => $intent['id'],
            'qr_image' => $qr['qr_image'],
            'status' => 'Pending',
        ];
    }
}
