<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use App\Models\CustomerModel;

class CustomerAuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function customerLogin(Request $request)
    {
        // Check rate limiting first
        $this->checkLoginAttempts($request);

        // Validate input
        $validated = $request->validate([
            'customer_email' => ['required', 'email'],
            'customer_password' => ['required', 'string'],
        ]);

        try {
            Log::info('Customer login attempt', [
                'email' => $validated['customer_email'],
                'ip' => $request->ip(),
            ]);

            $remember = $request->boolean('remember');

            // Find user
            $user = CustomerModel::where('customer_email', $validated['customer_email'])->first();

            // Single authentication check
            if (!$user) {
                $this->recordFailedAttempt($request);

                throw ValidationException::withMessages([
                    'customer_email' => ['The provided credentials are incorrect.'],
                ]);
            }

            if ($user->banned_until && Carbon::parse($user->banned_until)->isFuture()) {
                throw ValidationException::withMessages([
                    'customer_email' => [
                        "Your account is suspended until " .
                            Carbon::parse($user->banned_until)->format('Y-m-d H:i:s')
                    ],
                ]);
            }

            // Clear successful login attempts
            RateLimiter::clear($this->throttleKey($request));

            // Handle other devices logout if requested
            if ($request->boolean('logout_other_devices')) {
                $user->tokens()->delete();
            }

            // Generate token
            $token = $user->createToken('auth_token', ['customer:access'], $this->getTokenExpiration($remember))->plainTextToken;

            Log::info('Customer login successful', [
                'user_id' => $user->customer_id,
                'email' => $user->customer_email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user_id' => $user->customer_id,
                'first_name' => $user->first_name,
                'email' => $user->customer_email,
            ], 200);
        } catch (ValidationException $e) {
            // Re-throw validation exceptions as they already have proper formatting
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'email' => $validated['customer_email'] ?? 'unknown',
            ]);

            return response()->json([
                'message' => 'An unexpected server error occurred. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();

                Log::info('Customer logged out', [
                    'user_id' => $user->customer_id,
                    'ip' => $request->ip(),
                ]);
            }

            return response()->json([
                'message' => 'Logged out successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Error during logout. Please try again.'
            ], 500);
        }
    }

    protected function checkLoginAttempts(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($this->throttleKey($request));

            throw ValidationException::withMessages([
                'customer_email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ])->status(429);
        }
    }

    protected function recordFailedAttempt(Request $request)
    {
        RateLimiter::hit($this->throttleKey($request), $this->decayMinutes * 60);

        $remainingAttempts = $this->maxAttempts - RateLimiter::attempts($this->throttleKey($request));

        if ($remainingAttempts <= 0) {
            Log::warning('Account lockout threshold reached', [
                'email' => $request->input('customer_email'),
                'ip' => $request->ip(),
            ]);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(
            Str::lower($request->input('customer_email')) . '|' . $request->ip()
        );
    }

    protected function getTokenExpiration($remember = false)
    {
        return $remember ? now()->addDays(30) : now()->addDays(7);
    }
}
