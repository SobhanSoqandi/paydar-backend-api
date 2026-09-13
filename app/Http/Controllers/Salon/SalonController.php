<?php

namespace App\Http\Controllers\Salon;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Owner;
use App\Models\Salon;
use Illuminate\Http\Request;

class SalonController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'location' => ['required', 'string'],
            'back_percent' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $payload = $request->attributes->get('auth_user');

        $owner = Owner::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک سالن پیدا نشد',
            ], 404);
        }

        $salon = Salon::create([
            'name' => $data['name'],
            'location' => $data['location'],
            'back_percent' => $data['back_percent'] ?? 10,
            'owner_id' => $owner->id,
            'IsDeleted' => false,
        ]);

        $salon->load('owner.user');

        return response()->json([
            'data' => $salon,
            'message' => 'سالن با موفقیت ایجاد شد',
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $owner = Owner::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک سالن پیدا نشد',
            ], 404);
        }

        $salon = Salon::where('owner_id', $owner->id)
            ->where('IsDeleted', false)
            ->first();

        if (!$salon) {
            return response()->json([
                'data' => null,
                'message' => 'سالن پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string'],
            'back_percent' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $salon->update($data);
        $salon->load('owner.user');

        return response()->json([
            'data' => $salon,
            'message' => 'اطلاعات سالن با موفقیت ویرایش شد',
        ]);
    }

    public function getByUserId(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $owner = Owner::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک سالن پیدا نشد',
            ], 404);
        }

        $salon = Salon::where('owner_id', $owner->id)
            ->where('IsDeleted', false)
            ->first();

        if (!$salon) {
            return response()->json([
                'data' => null,
                'message' => 'سالن پیدا نشد',
            ], 404);
        }

        $salon->load('owner.user');

        return response()->json([
            'data' => $salon,
            'message' => 'اطلاعات سالن دریافت شد',
        ]);
    }

    public function search(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $owner = Owner::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک سالن پیدا نشد',
            ], 404);
        }

        $salon = Salon::where('owner_id', $owner->id)
            ->where('IsDeleted', false)
            ->first();

        if (!$salon) {
            return response()->json([
                'data' => null,
                'message' => 'سالن پیدا نشد',
            ], 404);
        }

        $query = Customer::with('user')
            ->where('salon_id', $salon->id);

        if ($request->filled('phone')) {
            $query->whereHas(
                'user',
                fn ($q) => $q->where(
                    'phone',
                    'like',
                    '%' . $request->phone . '%'
                )
            );
        }

        if ($request->filled('first_name')) {
            $query->where(
                'first_name',
                'like',
                '%' . $request->first_name . '%'
            );
        }

        if ($request->filled('last_name')) {
            $query->where(
                'last_name',
                'like',
                '%' . $request->last_name . '%'
            );
        }

        return response()->json([
            'data' => $query->get(),
            'message' => 'لیست مشتریان دریافت شد',
        ]);
    }

    public function services(int $salon_id)
    {
        $salon = Salon::find($salon_id);

        if (!$salon) {
            return response()->json([
                'data' => null,
                'message' => 'سالن پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $salon->services()
                ->where('is_active', true)
                ->get(),
            'message' => 'لیست خدمات دریافت شد',
        ]);
    }
}