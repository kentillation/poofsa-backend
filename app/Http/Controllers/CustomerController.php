<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\SendRecoveryCode;

use App\Models\CustomerModel;



class CustomerController extends Controller
{
    protected function getTokenExpiration($remember = false)
    {
        return $remember ? now()->addDays(30) : now()->addDays(7);
    }

    public function customerRegistration(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required|string|max:50',
                'middle_name' => 'nullable|string|max:50',
                'last_name' => 'required|string|max:50',
                'pet_name' => 'nullable|string|max:50',
                'customer_contact_number' => 'required|string|max:13|unique:tbl_customers,customer_contact_number',
                'customer_email' => 'required|email|max:50|unique:tbl_customers,customer_email',
                'customer_password' => 'required|min:8',
            ],
            [
                'customer_email.unique' => 'Email address already taken.',
                'customer_contact_number.unique' => 'Mobile number already taken.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {

            $customer = CustomerModel::create([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'pet_name' => $validated['pet_name'],
                'customer_contact_number' => $validated['customer_contact_number'],
                'customer_email' => $validated['customer_email'],
                'customer_password' => Hash::make($validated['customer_password']),
            ]);

            $remember = $request->boolean('remember');
            $token = $customer->createToken('auth_token', ['customer:access'], $this->getTokenExpiration($remember))->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "You’ve successfully registered",
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 60 * 24 * 30,
                'user_id' => $customer->customer_id,
                'first_name' => $customer->first_name,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Account registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'customer_email' => 'required|email'
            ]);

            $email = $validated['customer_email'];

            // Check if email exists
            $customer = CustomerModel::where('customer_email', $email)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found. Please try again!'
                ], 404);
            }

            // Generate recovery code
            $recoveryCode = rand(100000, 999999);

            // Save recovery code (IMPORTANT)
            $customer->recovery_code = $recoveryCode;
            $customer->recovery_code_expires_at = now()->addMinutes(10); // optional
            $customer->save();

            // Send email
            try {
                Mail::to($email)->send(new SendRecoveryCode($recoveryCode));
            } catch (\Exception $e) {
                Log::error('Mail error: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recovery code sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
