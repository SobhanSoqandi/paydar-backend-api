<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Owner;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:100'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'user_name' => ['nullable', 'string', 'max:100'],
            'password_hash' => ['required', 'string'],
            'salon_id' => ['nullable', 'integer'],
        ]);

        return DB::transaction(function () use ($data) {

            $role = $data['role_id']
                ? Role::find($data['role_id'])
                : Role::where('name', 'customer')->first();

            if (!$role) {
                return response()->json([
                    'data' => null,
                    'message' => 'نقش پیدا نشد',
                ], 404);
            }

            if (!empty($data['user_name']) &&
                User::where('user_name', $data['user_name'])->exists()) {
                return response()->json([
                    'data' => null,
                    'message' => 'این نام کاربری قبلاً استفاده شده است',
                ], 403);
            }

            if (!empty($data['email']) &&
                User::where('email', $data['email'])->exists()) {
                return response()->json([
                    'data' => null,
                    'message' => 'این ایمیل قبلاً استفاده شده است',
                ], 403);
            }

            $user = User::where('phone', $data['phone'])->first();

            if (!$user) {
                $user = User::create([
                    'user_name' => $data['user_name'] ?? null,
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'password_hash' => Hash::make($data['password_hash']),
                    'role_id' => $role->id,
                    'is_active' => true,
                ]);
            } else {
                $customerQuery = Customer::where('user_id', $user->id);

                if (array_key_exists('salon_id', $data) && $data['salon_id'] !== null) {
                    $customerQuery->where('salon_id', $data['salon_id']);
                } else {
                    $customerQuery->whereNull('salon_id');
                }

                if ($customerQuery->exists()) {
                    return response()->json([
                        'data' => null,
                        'message' => 'این کاربر در حال حاضر وجود دارد',
                    ], 403);
                }
            }

            if (strtolower($role->name) === 'customer') {

                $customer = Customer::create([
                    'user_id' => $user->id,
                    'salon_id' => $data['salon_id'] ?? null,
                ]);

                Wallet::create([
                    'customer_id' => $customer->id,
                    'balance' => 0,
                ]);

                Discount::create([
                    'customer_id' => $customer->id,
                    'max_usage' => 1,
                    'is_active' => true,
                    'percent' => 10,
                    'title' => 'welcom',
                    'start_date' => now(),
                    'end_date' => now()->addDays(30),
                ]);
            }

            if (strtolower($role->name) === 'owner') {
                if (!Owner::where('user_id', $user->id)->exists()) {
                    Owner::create([
                        'user_id' => $user->id,
                    ]);
                }
            }

            $user->load('role');

            return response()->json([
                'data' => $user,
                'message' => 'کاربر با موفقیت ایجاد شد',
            ]);
        });
    }

    public function index()
    {
        return User::with('role')->get();
    }

    public function me(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $user = User::with('role')->find($payload['sub'] ?? null);

        if (!$user) {
            return response()->json([
                'data' => null,
                'message' => 'کاربر پیدا نشد',
            ], 404);
        }

        return $user;
    }

    public function updateMe(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $user = User::find($payload['sub'] ?? null);

        if (!$user) {
            return response()->json([
                'data' => null,
                'message' => 'کاربر پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'phone' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
               'user_name' => [
                'nullable',
                
                'max:30',
            ],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update(array_filter(
            $data,
            fn ($value) => $value !== null
        ));

        $user->load('role');

        return response()->json([
            'data' => $user,
            'message' => 'اطلاعات کاربر با موفقیت ویرایش شد',
        ]);
    }

    public function delete(int $user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'data' => null,
                'message' => 'کاربر پیدا نشد',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'data' => $user,
            'message' => 'کاربر با موفقیت حذف شد',
        ]);
    }
}