<?php

namespace App\Http\Controllers;

use App\Services\PaymongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentModel;

class PaymentController extends Controller
{
    protected PaymongoService $paymongo;

    public function __construct(PaymongoService $paymongo)
    {
        $this->paymongo = $paymongo;
    }

    public function generateQr(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reference_number' => 'required|string',
        ]);

        $amount = (int) ($request->amount * 100);

        $intent = $this->paymongo->createPaymentIntent($amount, [
            'methods' => ['qrph'],
            'metadata' => [
                'reference_number' => $request->reference_number,
            ],
        ]);

        abort_if(!$intent['ok'], 500, 'Payment intent failed');

        $qr = $this->paymongo->attachQrph(
            $intent['id'],
            [
                'name' => 'Poofsa Cashier',
                'email' => 'cashier@poofsa.com',
                'phone' => '09453145499',
            ]
        );

        abort_if(!$qr['ok'], 500, 'QR generation failed');

        PaymentModel::create([
            'payment_intent_id' => $intent['id'],
            'reference_number' => $request->reference_number,
            'amount' => $amount,
            'status' => 'Pending',
        ]);

        return response()->json([
            'payment_intent_id' => $intent['id'],
            'qr_image' => $qr['qr_image'],
            'status' => 'pending',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'      => ['required', 'numeric', 'min:100'],
            'methods'     => ['nullable', 'array'],
            'methods.*'   => ['in:gcash,paymaya,qrph'],
            'metadata'    => ['nullable', 'array'],
        ]);

        $amountInCentavos = $validated['amount'] * 100;
        $result = $this->paymongo->createPaymentIntent(
            $amountInCentavos,
            [
                'methods'     => $validated['methods'] ?? ['gcash', 'paymaya', 'qrph'],
                'metadata'    => $validated['metadata'] ?? null,
            ]
        );

        if (!$result['ok']) {
            Log::warning('PayMongo API creation failed', $result);
            return response()->json([
                'message' => 'Unable to create payment intent',
                'errors'  => $result['body'] ?? null,
            ], $result['status'] ?? 500);
        }

        return response()->json([
            'payment_intent_id' => $result['id'],
            'client_key'        => $result['client_key'],
            'status'            => $result['status'],
        ]);
    }

    public function attach(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'type'              => 'required|in:gcash,paymaya,qrph',
            'billing'           => 'required|array',
            'billing.name'      => 'required|string|max:100',
            'billing.email'     => 'required|email',
            'billing.phone'     => 'required|string|max:20',
        ]);

        try {
            $result = $this->paymongo->attachPaymentMethod(
                $validated['payment_intent_id'],
                $validated['type'],
                $validated['billing']
            );

            if (!$result['ok']) {
                return response()->json([
                    'message' => 'Failed to attach payment method',
                    'errors'  => $result['body'] ?? null,
                ], $result['status'] ?? 500);
            }

            return response()->json([
                'payment_method_id' => $result['id'] ?? null,
                'status'            => $result['status'] ?? null,
                'redirect_url'      => $result['redirect_url'] ?? null,
                'qr_image'          => $result['qr_image'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayMongo attach error', ['exception' => $e]);
            return response()->json([
                'message' => 'Server error',
            ], 500);
        }
    }

    public function checkStatus(string $intentId)
    {
        try {
            $result = $this->paymongo->monitorPaymentIntent($intentId, true);

            if (!$result['ok']) {
                return response()->json([
                    'ok' => false,
                    'status' => $result['status'] ?? 'error',
                    'message' => 'Failed to check payment status',
                    'error' => $result['body']['error'] ?? null,
                ], $result['status'] ?? 500);
            }

            if ($result['original_status'] === 'succeeded') {
                PaymentModel::where('payment_intent_id', $intentId)
                    ->update(['status' => 'Paid',
                        'paid_at' => now(),
                        'reference_number' => $result['metadata']['reference_number']]);
            }

            return response()->json([
                'ok' => true,
                'status' => $result['status'],
                'original_status' => $result['original_status'] ?? null,
                'payment_intent_id' => $result['id'],
                'amount' => $result['amount'],
                'paid_at' => now(),
                'metadata' => $result['metadata'],
                'latest_payment' => $result['latest_payment'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Check payment status error', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => 'Server error checking payment status',
            ], 500);
        }
    }
}
