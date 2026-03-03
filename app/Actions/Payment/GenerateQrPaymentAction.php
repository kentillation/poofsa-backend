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

        // 🔐 Idempotency check
        $existing = PaymentModel::where('idempotency_key', $idempotencyKey)->first();

        if ($existing && $existing->payment_intent_id) {
            return [
                'payment_intent_id' => $existing->payment_intent_id,
                'qr_image' => $existing->qr_image,
                'status' => $existing->status,
            ];
        }

        $amountInCentavos = $amount * 100;

        // 🔁 Retry logic handled inside repository
        $intent = $this->paymongo->createPaymentIntent(
            $amountInCentavos,
            [
                'methods' => ['qrph'],
                'metadata' => [
                    'reference_number' => $referenceNumber,
                ],
            ]
        );

        if (!$intent['ok']) {
            throw new \RuntimeException('Payment intent failed');
        }

        $qr = $this->paymongo->attachQrph($intent['id'], [
            'name' => 'Poofsa Cashier',
            'email' => 'cashier@poofsa.com',
            'phone' => '09453145499',
        ]);

        if (!$qr['ok']) {
            throw new \RuntimeException('QR generation failed');
        }

        $payment = PaymentModel::create([
            'idempotency_key' => $idempotencyKey,
            'payment_intent_id' => $intent['id'],
            'reference_number' => $referenceNumber,
            'amount' => $amountInCentavos,
            'status' => 'pending',
            'qr_image' => $qr['qr_image'],
        ]);

        return [
            'payment_intent_id' => $payment->payment_intent_id,
            'qr_image' => $payment->qr_image,
            'status' => $payment->status,
        ];
    }
}
