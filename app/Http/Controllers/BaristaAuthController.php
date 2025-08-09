<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;
use App\Models\ShopModel;
use App\Models\BranchModel;

class BaristaAuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function login(Request $request)
    {
        try {
            $this->checkLoginAttempts($request);
            $validated = $request->validate([
                'barista_email' => ['required', 'string'],
                'barista_password' => ['required', 'string'],
                'device_name' => ['nullable', 'string', 'max:255'],
            ]);

            $remember = $request->boolean('remember');

            $user = \App\Models\BaristaModel::where('barista_email', $validated['barista_email'])->first();
    
            if (!$user || !Hash::check($validated['barista_password'], $user->barista_password)) {
                RateLimiter::hit($this->throttleKey($request));
                throw ValidationException::withMessages([
                    'barista_email' => [trans('auth.failed')],
                ]);
            }

            if ($user->banned_until && Carbon::parse($user->banned_until)->isFuture()) {
                abort(403, 'Your account is suspended until ' . Carbon::parse($user->banned_until)->toDateTimeString());
            }
            if ($user->banned_until && Carbon::parse($user->banned_until)->isPast()) {
                $user->update(['banned_until' => null]);
            }
            if ($request->boolean('logout_other_devices')) {
                $user->tokens()->delete();
            }

            $token = $user->createToken(
                'barista_auth_token',
                ['barista'],
                $this->getTokenExpiration($remember)
            )->plainTextToken;

            $shop = ShopModel::find($user->shop_id);
            $branch = BranchModel::find($user->branch_id); // Get branch details

            RateLimiter::clear($this->throttleKey($request));

            $cookie = cookie(
                'XSRF-TOKEN',
                $token,
                config('session.lifetime'),
                '/',
                config('session.domain', null),
                config('session.secure', true),
                true,
                false,
                'Strict'
            );

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 60 * 24 * ($remember ? 30 : 7),
                'shop_id' => $user->shop_id,
                'shop_name' => $shop ? $shop->shop_name : null,
                'user_id' => $user->barista_id,
                'branch_id' => $user->branch_id,
                'branch_name' => $branch ? $branch->branch_name : null,
                'branch_location' => $branch ? $branch->branch_location : null,
                'contact' => $branch ? $branch->contact : null,
            ])->withCookie($cookie);
        } catch (\Exception $e) {
            Log::error('Barista login error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()?->delete();
            Auth::guard('barista')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Logged out successfully.']);
        } catch (\Exception $e) {
            Log::error('Barista logout error: ' . $e->getMessage());
            return response()->json(['message' => 'Logged out successfully.']);
        }
    }

    protected function checkLoginAttempts(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            throw ValidationException::withMessages([
                'barista_email' => [trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])],
            ])->status(429);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input('barista_email')) . '|' . $request->ip());
    }

    protected function getTokenExpiration($remember = false)
    {
        return $remember
            ? now()->addDays(30)
            : now()->addDays(7);
    }
}
