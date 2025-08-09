<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ShopModel;
use App\Models\BranchModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function login(Request $request)
    {
        $this->checkLoginAttempts($request);
        $validated = $request->validate([
            'cashier_email' => ['required', 'string'],
            'cashier_password' => ['required', 'string'],
        ]);
        $credentials = ['cashier_email' => $validated['cashier_email'], 'password' => $validated['cashier_password']];
        $remember = $request->boolean('remember');
        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'cashier_email' => [trans('auth.failed')],
            ]);
        }
        /** @var \App\Models\CashierModel $user */
        $user = Auth::user();
        if ($user->banned_until && Carbon::parse($user->banned_until)->isFuture()) {
            abort(403, 'Your account is suspended until ' . Carbon::parse($user->banned_until)->toDateTimeString());
        }
        if ($user->banned_until && Carbon::parse($user->banned_until)->isPast()) {
            $user->update(['banned_until' => null]);
        }
        if ($request->boolean('logout_other_devices')) {
            $user->tokens()->delete();
        }
        $token = $user->createToken('auth_token', ['*'], $this->getTokenExpiration($remember))->plainTextToken;
        $shop = ShopModel::find($user->shop_id);
        $shopName = $shop ? $shop->shop_name : null;
        $branch = BranchModel::find($user->branch_id);
        $branchName = $branch ? $branch->branch_name : null;
        $branchLocationName = $branch ? $branch->branch_location : null;
        $branchContact = $branch ? $branch->contact : null;
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
            'shop_name' => $shopName,
            'branch_name' => $branchName,
            'branch_location' => $branchLocationName,
            'contact' => $branchContact,
            'user_id' => $user->cashier_id,
        ])->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()?->delete();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Logged out successfully.']);
        } catch (\Exception $e) {
            Log::error('Logout error: '.$e->getMessage());
            return response()->json(['message' => 'Logged out successfully.']);
        }
    }

    protected function checkLoginAttempts(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            throw ValidationException::withMessages([
                'cashier_email' => [trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])],
            ])->status(429);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input('cashier_email')) . '|' . $request->ip());
    }

    protected function getTokenExpiration($remember = false)
    {
        return $remember
            ? now()->addDays(30)
            : now()->addDays(7);
    }
}
