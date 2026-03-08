<?php

namespace App\Http\Controllers;

use App\Actions\Payment\GenerateQrPaymentAction;
use App\Services\PaymongoService;
use App\Http\Resources\GenerateQrResource;
use App\Http\Requests\GenerateQrRequest;
use App\Models\PaymentModel;
use App\Events\PaymentSucceeded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class PaymentController extends Controller
{
    protected PaymongoService $paymongo;

    public function __construct(PaymongoService $paymongo)
    {
        $this->paymongo = $paymongo;
    }

    public function generatingQRCode(GenerateQrRequest $request, GenerateQrPaymentAction $action)
    {
        $result = $action->execute(
            amount: $request->amount,
            referenceNumber: $request->reference_number
        );

        return new GenerateQrResource($result);
    }

    public function handlePayment(Request $request)
    {
        try {

            $payload = $request->all();

            Log::info('PayMongo Webhook Received', $payload);

            $eventType = $payload['data']['attributes']['type'] ?? null;

            $paymentIntentId = $payload['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;

            if (!$paymentIntentId) {
                return response()->json(['message' => 'Invalid payload'], 400);
            }

            $payment = PaymentModel::where('payment_intent_id', $paymentIntentId)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            switch ($eventType) {

                case 'payment.paid':

                    $payment->update([
                        'status' => PaymentModel::STATUS_PAID
                    ]);

                    broadcast(new PaymentSucceeded($payment->reference_number));

                    break;

                case 'payment.failed':

                    $payment->update([
                        'status' => PaymentModel::STATUS_FAILED
                    ]);

                    break;
            }

            return response()->json([
                'message' => 'Webhook processed'
            ]);
        } catch (\Throwable $e) {

            Log::error('PayMongo Webhook Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Webhook failed'
            ], 500);
        }
    }

}
