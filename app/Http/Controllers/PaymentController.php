<?php

namespace App\Http\Controllers;

use App\Actions\Payment\GenerateQrPaymentAction;
use App\Services\PaymongoService;
use App\Http\Resources\GenerateQrResource;
use App\Http\Requests\GenerateQrRequest;
use App\Models\PaymentModel;
use App\Events\PaymentSucceeded;
// use App\Events\PaymentFailed;
use Illuminate\Support\Facades\DB;
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
        $payload = $request->all();

        $paymentIntentId = "pi_x67tmadUPUEhTAZBtddqqNKC";
        // $paymentIntentId = data_get(
        //     $payload,
        //     'data.attributes.data.attributes.payment_intent_id'
        // );

        $referenceNumber = "867896185543";
        // $referenceNumber = data_get(
        //     $payload,
        //     'data.attributes.data.attributes.metadata.reference_number'
        // );

        if (!$paymentIntentId || !$referenceNumber) {
            Log::warning('Invalid PayMongo webhook payload', [
                'payment_intent_id' => $paymentIntentId,
                'reference_number' => $referenceNumber,
            ]);

            // Insert error event

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }

        try {

            $payment = PaymentModel::where(
                'payment_intent_id',
                $paymentIntentId
            )->first();

            if (!$payment) {
                Log::warning('Payment not found', [
                    'payment_intent_id' => $paymentIntentId,
                    'reference_number' => $referenceNumber,
                ]);

                // Insert error event

                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error',
                ], 500);
            }

            $eventType = 'payment.paid';
            // $eventType = data_get($payload, 'data.attributes.type');

            switch ($eventType) {

                case 'payment.paid':

                    $paidAt = now();
                    // $paidAt = data_get(
                    //     $payload,
                    //     'data.attributes.data.attributes.paid_at'
                    // );

                    if (!$paidAt) {
                        Log::warning('Missing payment date', [
                            'paid_at' => $paidAt,
                        ]);

                        // Insert error event

                        return response()->json([
                            'success' => false,
                            'message' => 'Internal server error',
                        ], 500);
                    }

                    // Prevent duplicate processing.
                    if ($payment->status === PaymentModel::STATUS_PAID) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Payment already processed',
                        ], 200);
                    }

                    DB::transaction(function () use ($payment, $paidAt) {
                        $payment->update([
                            'status' => PaymentModel::STATUS_PAID,
                            'paid_at' => $paidAt,
                        ]);
                    });

                    Log::info('Payment successfully processed', [
                        'reference_number' => $referenceNumber,
                        'paid_at' => $paidAt,
                    ]);

                    broadcast(new PaymentSucceeded($referenceNumber));

                    break;

                case 'payment.failed':

                    $payment->update([
                        'status' => PaymentModel::STATUS_FAILED,
                    ]);

                    Log::warning('Payment failed', [
                        'reference_number' => $referenceNumber,
                        'payment_intent_id' => $paymentIntentId,
                    ]);

                    // broadcast(
                    //     new PaymentFailed($referenceNumber)
                    // );

                    return response()->json([
                        'success' => true,
                        'event' => 'payment.failed',
                        'message' => 'Payment has failed',
                        'data' => [
                            'reference_number' => $referenceNumber,
                            'status' => PaymentModel::STATUS_FAILED,
                        ],
                    ], 200);

                    break;

                default:

                    Log::warning('Unhandled PayMongo webhook event', [
                        'event_type' => $eventType,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Internal server error',
                    ], 500);

                    break;
            }
        } catch (\Throwable $e) {

            Log::error('PayMongo webhook processing failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
                'reference_number' => $referenceNumber,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing payment',
            ], 500);
        }
    }
}
