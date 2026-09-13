<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'birthday' => ['nullable', 'date'],
            'profile_image' => ['nullable', 'string', 'max:500'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'salon_id' => ['nullable', 'integer', 'exists:salons,id'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = Customer::create($data);

        $customer->load('user');

        return response()->json([
            'data' => $customer,
            'message' => 'مشتری با موفقیت ایجاد شد',
        ], 201);
    }

    public function index()
    {
        $customers = Customer::with('user')->get();

        return response()->json([
            'data' => $customers,
            'message' => 'لیست مشتریان با موفقیت دریافت شد',
        ]);
    }

    public function me(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $customer = Customer::with('user')
            ->where('user_id', $payload['sub'] ?? null)
            ->first();

        if (!$customer) {
            return response()->json([
                'data' => null,
                'message' => 'مشتری پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $customer,
            'message' => 'اطلاعات مشتری دریافت شد',
        ]);
    }

    public function show(int $customer_id)
    {
        $customer = Customer::with('user')->find($customer_id);

        if (!$customer) {
            return response()->json([
                'data' => null,
                'message' => 'مشتری پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $customer,
            'message' => 'اطلاعات مشتری دریافت شد',
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $customer = Customer::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$customer) {
            return response()->json([
                'data' => null,
                'message' => 'مشتری پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'birthday' => ['nullable', 'date'],
            'profile_image' => ['nullable', 'string', 'max:500'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
        ]);

        $customer->update($data);
        $customer->load('user');

        return response()->json([
            'data' => $customer,
            'message' => 'اطلاعات مشتری با موفقیت ویرایش شد',
        ]);
    }

    public function delete(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $customer = Customer::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$customer) {
            return response()->json([
                'data' => null,
                'message' => 'مشتری پیدا نشد',
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'data' => $customer,
            'message' => 'مشتری با موفقیت حذف شد',
        ]);
    }

    public function appointments(Request $request)
    {
        $customerId = $request->query('customer_id');

        $appointments = Appointment::with([
            'customer.user',
            'salon',
            'services',
        ])
            ->when(
                $customerId,
                fn ($query) => $query->where('customer_id', $customerId)
            )
            ->get();

        return response()->json([
            'data' => $appointments,
            'message' => 'لیست نوبت‌های مشتری دریافت شد',
        ]);
    }
}