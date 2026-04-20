<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class CustomerAuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function customerLogin(Request $request)
    {
        $this->checkLoginAttempts($request);

        $validated = $request->validate([
            'customer_email' => ['required', 'email'],
            'customer_password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // Use session guard for login attempt
        if (!Auth::guard('customer')->attempt([
            'customer_email' => $validated['customer_email'],
            'password' => $validated['customer_password'],
        ], $remember)) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'customer_email' => [trans('auth.failed')],
            ]);
        }

        /** @var \App\Models\CustomerModel $user */
        $user = Auth::guard('customer')->user();

        // Check banned status
        if ($user->banned_until && Carbon::parse($user->banned_until)->isFuture()) {
            Auth::guard('customer')->logout();
            abort(403, 'Your account is suspended until ' . $user->banned_until);
        }

        if ($user->banned_until && Carbon::parse($user->banned_until)->isPast()) {
            $user->update(['banned_until' => null]);
        }

        if ($request->boolean('logout_other_devices')) {
            $user->tokens()->delete();
        }

        // Create Sanctum token for API access
        $token = $user->createToken('auth_token', ['*'], $this->getTokenExpiration($remember))->plainTextToken;

        RateLimiter::clear($this->throttleKey($request));

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 60 * 24 * ($remember ? 30 : 7),
            'user_id' => $user->customer_id,
            'first_name' => $user->first_name,
        ]);
    }

    public function logout(Request $request)
    {
        // The auth:customer_api middleware ensures we have a token
        $request->user()->currentAccessToken()?->delete();

        // Also logout from session guard
        Auth::guard('customer')->logout();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    protected function checkLoginAttempts(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            throw ValidationException::withMessages([
                'customer_email' => [trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])],
            ])->status(429);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input('customer_email')) . '|' . $request->ip());
    }

    protected function getTokenExpiration($remember = false)
    {
        return $remember
            ? now()->addDays(30)
            : now()->addDays(7);
    }
}
