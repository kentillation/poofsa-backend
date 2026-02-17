<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\ShopModel;
use App\Models\BranchModel;
use App\Models\AdminModel;
use App\Models\CashierModel;
use App\Models\KitchenModel;
use App\Models\BaristaModel;

class DevController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function login(Request $request)
    {
        $this->checkLoginAttempts($request);
        $validated = $request->validate([
            'dev_email' => ['required', 'string'],
            'dev_password' => ['required', 'string'],
        ]);
        $remember = $request->boolean('remember');
        if (!Auth::guard('dev')->attempt([
            'dev_email' => $validated['dev_email'],
            'password' => $validated['dev_password'],
        ], $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'dev_email' => [trans('auth.failed')],
            ]);
        }
        /** @var \App\Models\DevModel $user */
        $user = Auth::guard('dev')->user();
        if ($user->banned_until && Carbon::parse($user->banned_until)->isFuture()) {
            abort(403, 'Your account is suspended until ' . $user->banned_until);
        }
        if ($user->banned_until && Carbon::parse($user->banned_until)->isPast()) {
            $user->update(['banned_until' => null]);
        }
        if ($request->boolean('logout_other_devices')) {
            $user->tokens()->delete();
        }
        $token = $user->createToken($validated['device_name'] ?? 'auth_token', ['*'], $this->getTokenExpiration($remember))->plainTextToken;
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
        ])->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()?->delete();
            Auth::guard('dev')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Logged out successfully.']);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['message' => 'Logged out successfully.']);
        }
    }

    protected function checkLoginAttempts(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            throw ValidationException::withMessages([
                'dev_email' => [trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])],
            ])->status(429);
        }
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input('dev_email')) . '|' . $request->ip());
    }

    protected function getTokenExpiration($remember = false)
    {
        return $remember
            ? now()->addDays(30)
            : now()->addDays(7);
    }

    public function getShops()
    {
        try {
            $data = ShopModel::all();
            return response()->json([
                'status' => true,
                'message' => $data->isEmpty() ? 'No stores found!' : 'Stores fetched successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stores!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getShopBranches($shopId)
    {
        try {
            $branches = BranchModel::where('shop_id', $shopId)
                ->pluck('branch_name');
            return response()->json([
                'success' => true,
                'message' => $branches->isEmpty() ? 'No store branch found!' : 'Store branches fetched successfully!',
                'branches' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching store branch!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveShop(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:50',
            'shop_owner' => 'required|string|max:50',
            'shop_location' => 'required|string',
            'shop_email' => 'required|string|email|max:50|unique:tbl_shops,shop_email',
            'shop_contact_number' => 'required|string|max:13',
            'shop_status_id' => 'required|integer|min:1',

            'branch_name' => 'required|string|max:50',
            'branch_location' => 'required|string',
            'm_name' => 'required|string|max:50',
            'm_email' => 'required|string|email|max:50|unique:tbl_shop_branch,m_email',
            'contact' => 'required|string|max:13',
            'status_id' => 'required|integer|min:1',

            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|max:255|unique:tbl_admin,admin_email',
            'admin_password' => 'required|string|min:8',
            'admin_mpin' => 'required|digits:6|numeric',

            'cashier_name' => 'required|string|max:255',
            'cashier_email' => 'required|string|email|max:255|unique:tbl_cashier,cashier_email',
            'cashier_password' => 'required|string|min:8',
            'cashier_mpin' => 'required|digits:6|numeric',

            'kitchen_name' => 'required|string|max:255',
            'kitchen_email' => 'required|string|email|max:255|unique:tbl_kitchen,kitchen_email',
            'kitchen_password' => 'required|string|min:8',
            'kitchen_mpin' => 'required|digits:6|numeric',

            'barista_name' => 'required|string|max:255',
            'barista_email' => 'required|string|email|max:255|unique:tbl_barista,barista_email',
            'barista_password' => 'required|string|min:8',
            'barista_mpin' => 'required|digits:6|numeric',
        ]);

        DB::beginTransaction();

        try {

            $shop = ShopModel::create([
                'shop_name' => $validated['shop_name'],
                'shop_owner' => $validated['shop_owner'],
                'shop_location' => $validated['shop_location'],
                'shop_email' => $validated['shop_email'],
                'shop_contact_number' => $validated['shop_contact_number'],
                'shop_status_id' => $validated['shop_status_id'],
            ]);
            $shopId = $shop->shop_id;

            $branch = BranchModel::create([
                'shop_id' => $shopId,
                'branch_name' => $validated['branch_name'],
                'branch_location' => $validated['branch_location'],
                'm_name' => $validated['m_name'],
                'm_email' => $validated['m_email'],
                'contact' => $validated['contact'],
                'status_id' => $validated['status_id'],
            ]);
            $branchId = $branch->branch_id;

            $admin = AdminModel::create([
                'admin_name' => $validated['admin_name'],
                'admin_email' => $validated['admin_email'],
                'admin_password' => Hash::make($validated['admin_password']),
                'admin_mpin' => $validated['admin_mpin'],
                'shop_id' => $shopId,
            ]);

            $cashier = CashierModel::create([
                'cashier_name' => $validated['cashier_name'],
                'cashier_email' => $validated['cashier_email'],
                'cashier_password' => Hash::make($validated['cashier_password']),
                'cashier_mpin' => $validated['cashier_mpin'],
                'shop_id' => $shopId,
                'branch_id' => $branchId,
            ]);

            $kitchen = KitchenModel::create([
                'kitchen_name' => $validated['kitchen_name'],
                'kitchen_email' => $validated['kitchen_email'],
                'kitchen_password' => Hash::make($validated['kitchen_password']),
                'kitchen_mpin' => $validated['kitchen_mpin'],
                'shop_id' => $shopId,
                'branch_id' => $branchId,
            ]);

            $barista = BaristaModel::create([
                'barista_name' => $validated['barista_name'],
                'barista_email' => $validated['barista_email'],
                'barista_password' => Hash::make($validated['barista_password']),
                'barista_mpin' => $validated['barista_mpin'],
                'shop_id' => $shopId,
                'branch_id' => 5,
            ]);

            $token = $shop->createToken('auth-token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Registration successful',
                'shop' => $shop,
                'branch' => $branch,
                'admin' => $admin->makeHidden(['admin_password', 'admin_mpin']),
                'cashier' => $cashier->makeHidden(['cashier_password', 'cashier_mpin']),
                'kitchen' => $kitchen->makeHidden(['kitchen_password', 'kitchen_mpin']),
                'barista' => $barista->makeHidden(['barista_password', 'barista_mpin']),
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
