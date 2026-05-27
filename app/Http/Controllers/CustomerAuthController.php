<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;
use App\Models\CustomerModel;
use App\Models\RefreshTokenModel;

class CustomerAuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function customerLogin(Request $request)
    {
        // Validate input first (doesn't consume rate limiter)
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

            // Check if user is banned FIRST - before any rate limiting or password checking
            if ($user && $user->banned_until && Carbon::parse($user->banned_until)->isFuture()) {
                Log::warning('Blocked login attempt for banned account', [
                    'email' => $validated['customer_email'],
                    'ip' => $request->ip(),
                    'banned_until' => $user->banned_until,
                ]);

                throw ValidationException::withMessages([
                    'user_error' => [
                        "Your account is suspended until " .
                            Carbon::parse($user->banned_until)->format('Y-m-d H:i:s')
                    ],
                ])->status(403);
            }

            // Check rate limiting ONLY for non-banned users
            $this->checkLoginAttempts($request);

            // Authenticate user
            if (!$user || !Hash::check($validated['customer_password'], $user->customer_password)) {
                $this->recordFailedAttempt($request);

                Log::warning('Failed login attempt - invalid credentials', [
                    'email' => $validated['customer_email'],
                    'ip' => $request->ip(),
                    'exists' => !is_null($user),
                ]);

                throw ValidationException::withMessages([
                    'user_error' => ['The provided credentials are incorrect.'],
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
            $formattedTime = $this->formatLockoutTime($seconds);

            // Calculate total lockout period
            $totalLockoutMinutes = $this->decayMinutes;

            Log::warning('Account locked due to too many attempts', [
                'email' => $request->input('customer_email'),
                'ip' => $request->ip(),
                'seconds_remaining' => $seconds,
                'total_lockout_minutes' => $totalLockoutMinutes,
            ]);

            throw ValidationException::withMessages([
                'user_error' => [
                    "Too many login attempts. Your account is temporarily locked for {$totalLockoutMinutes} minutes. " .
                        "Please try again in {$formattedTime}."
                ],
            ])->status(429);
        }
    }

    protected function recordFailedAttempt(Request $request)
    {
        RateLimiter::hit($this->throttleKey($request), $this->decayMinutes * 60);

        $attempts = RateLimiter::attempts($this->throttleKey($request));
        $remainingAttempts = $this->maxAttempts - $attempts;

        Log::warning('Failed login attempt recorded', [
            'email' => $request->input('customer_email'),
            'ip' => $request->ip(),
            'attempts' => $attempts,
            'max_attempts' => $this->maxAttempts,
            'remaining_attempts' => $remainingAttempts,
        ]);

        // Show warning when only 1-2 attempts remain
        if ($remainingAttempts > 0 && $remainingAttempts <= 2) {
            throw ValidationException::withMessages([
                'user_error' => [
                    "Invalid credentials. You have {$remainingAttempts} more attempt" .
                        ($remainingAttempts > 1 ? "s" : "") . " before temporary lockout."
                ],
            ]);
        }

        if ($remainingAttempts <= 0) {
            Log::warning('Account lockout threshold reached', [
                'email' => $request->input('customer_email'),
                'ip' => $request->ip(),
                'total_attempts' => $attempts,
            ]);
        }
    }

    protected function formatLockoutTime($seconds)
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0 && $remainingSeconds > 0) {
            return "{$minutes} minute" . ($minutes > 1 ? "s" : "") .
                " and {$remainingSeconds} second" . ($remainingSeconds > 1 ? "s" : "");
        } elseif ($minutes > 0) {
            return "{$minutes} minute" . ($minutes > 1 ? "s" : "");
        } elseif ($remainingSeconds > 0) {
            return "{$remainingSeconds} second" . ($remainingSeconds > 1 ? "s" : "");
        }

        return "a few moments";
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(
            Str::lower($request->input('customer_email')) . '|' . $request->ip()
        );
    }

    protected function getTokenExpiration()
    {
        return now()->addMinutes(2);
        // return now()->addSeconds(5); // For testing only
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json([
                'message' => 'Missing refresh token'
            ], 401);
        }

        // Find token in DB
        $hashed = hash('sha256', $refreshToken);

        $tokenRecord = RefreshTokenModel::where('token_hash', $hashed)
            ->whereNull('revoked_at')
            ->first();

        // Invalid token
        if (!$tokenRecord) {
            return response()->json([
                'message' => 'Invalid refresh token'
            ], 401);
        }

        // Expired token
        if ($tokenRecord->expires_at < now()) {
            $tokenRecord->update(['revoked_at' => now()]);

            return response()->json([
                'message' => 'Refresh token expired'
            ], 401);
        }

        // Get user
        $user = CustomerModel::find($tokenRecord->user_id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 401);
        }

        // ROTATE refresh token (invalidate old one)
        $tokenRecord->update([
            'revoked_at' => now()
        ]);

        $newRefreshToken = Str::random(64);

        RefreshTokenModel::create([
            'user_id' => $user->customer_id,
            'token_hash' => hash('sha256', $newRefreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        // Create new access token (SHORT LIVED)
        $accessToken = $user->createToken(
            'access_token',
            ['customer:access'],
            now()->addMinutes(15)
        )->plainTextToken;

        // Send new refresh cookie
        return response()->json([
            'access_token' => $accessToken,
            'expires_in' => 900,
        ])->cookie(
            'refresh_token',
            $newRefreshToken,
            60 * 24 * 30, // 30 days
            null,
            null,
            true,  // Secure
            true,  // HttpOnly
            false,
            'Strict'
        );
    }
}
