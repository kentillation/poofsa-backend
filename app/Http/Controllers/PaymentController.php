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
        $message = [
            'invalidPaymentIntentId' => 'Invalid payment intent id',
            'invalidReferenceNumber' => 'Invalid reference number',
            'paymentNotFound' => 'Payment not found',
            'noDateForPaidPayment' => 'No date for paid payment',
            'realTimeUpdateFailed' => 'Real-time update failed',
            'paymentSuccess' => 'Payment successfully proccessed',
            'paymentFailed' => 'Payment has been failed',
            'webhookFailed' => 'An error occurred with the third-party payment gateway'
        ];

        try {

            $payload = $request->all();

            $paymentIntentId = data_get($payload, 'data.attributes.data.attributes.payment_intent_id');
            if (empty($paymentIntentId)) {
                return response()->json(['message' => $message['invalidPaymentIntentId']], 404);
            }

            $referenceNumber = data_get($payload, 'data.attributes.data.attributes.metadata.reference_number');
            if (empty($referenceNumber)) {
                return response()->json(['message' => $message['invalidReferenceNumber']], 404);
            }

            $payment = PaymentModel::where('payment_intent_id', $paymentIntentId)->first();

            if (!$payment) {
                return response()->json(['message' => $message['paymentNotFound']], 404);
            }

            $eventType = data_get($payload, 'data.attributes.type');

            switch ($eventType) {

                case 'payment.paid':

                    $paidAt = data_get($payload, 'data.attributes.data.attributes.paid_at');

                    if (empty($paidAt)) {
                        return response()->json(['message' => $message['noDateForPaidPayment']], 404);
                    } else {
                        $payment->update([
                            'status' => PaymentModel::STATUS_PAID,
                            'paid_at' => $paidAt
                        ]);

                        Log::info('Succeed payment has been save in database: ', [
                            'reference_number' => $referenceNumber,
                            'paid_at' => $paidAt
                        ]);

                        $updateUI = broadcast(new PaymentSucceeded($referenceNumber));

                        if (!$updateUI) {
                            Log::error('Failed to broadcast PaymentSucceeded event', [
                                'reference_number' => $referenceNumber
                            ]);
                            return response()->json(['message' => $message['realTimeUpdateFailed']], 400);
                        } else {
                            Log::info('PaymentSucceeded event broadcasted successfully', [
                                'reference_number' => $referenceNumber
                            ]);
                            return response()->json(['message' => $message['paymentSuccess']], 201);
                        }
                    }

                    break;

                case 'payment.failed':
                    Log::error('Payment failed for payment intent ID: ' . $paymentIntentId);

                    $payment->update([
                        'status' => PaymentModel::STATUS_FAILED
                    ]);

                    return response()->json(['message' => $message['paymentFailed'] . 'with reference number: ' . $referenceNumber], 404);

                    break;
            }
        } catch (\Throwable $e) {

            Log::error('PayMongo Webhook Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => $message['webhookFailed']], 500);
        }
    }
}
