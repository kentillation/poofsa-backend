<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentModel;

class PaymongoWebhookController extends Controller
{
    public function handle(Request $request, $paymentIntentId)
    {
        $payload = $request->all();
        $event = $payload['data']['attributes']['type'] ?? null;

        Log::info('PayMongo webhook received', $payload);

        if ($event !== 'payment.paid') {
            return response()->json(['ok' => true]);
        }

        $paymentData = $payload['data']['attributes']['data'];
        // $paymentIntentId = $paymentData['attributes']['payment_intent_id'];
        $amount = $paymentData['attributes']['amount'];

        $payment = PaymentModel::where('payment_intent_id', $paymentIntentId)->first();

        if (!$payment) {
            Log::warning('Payment not found for intent', [$paymentIntentId]);
            return response()->json(['ok' => true]);
        }

        // 🔒 Idempotent
        if ($payment->status === 'paid') {
            return response()->json(['ok' => true]);
        }

        $payment->update([
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => now(),
            'paymongo_payment_id' => $paymentData['id'],
        ]);

        // 🔔 trigger receipt, inventory, etc.

        return response()->json(['ok' => true]);
    }
}
