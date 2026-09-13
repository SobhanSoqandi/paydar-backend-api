<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Service;
use App\Models\Salon;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'salon_id' => ['nullable', 'integer', 'exists:salons,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = $request->attributes->get('auth_user');

        $owner = Owner::where(
            'user_id',
            $payload['sub'] ?? null
        )->first();

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک پیدا نشد',
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

        $service = Service::create([
            'name' => $data['name'],
            'salon_id' => $data['salon_id'] ?? $salon->id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $service,
            'message' => 'خدمت با موفقیت ایجاد شد',
        ], 201);
    }

    public function index()
    {
        return response()->json([
            'data' => Service::with('salon')->get(),
            'message' => 'لیست خدمات دریافت شد',
        ]);
    }

    public function show(int $service_id)
    {
        $service = Service::with('salon')->find($service_id);

        if (!$service) {
            return response()->json([
                'data' => null,
                'message' => 'خدمت پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $service,
            'message' => 'اطلاعات خدمت دریافت شد',
        ]);
    }

    public function update(Request $request, int $service_id)
    {
        $service = Service::find($service_id);

        if (!$service) {
            return response()->json([
                'data' => null,
                'message' => 'خدمت پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'salon_id' => ['nullable', 'integer', 'exists:salons,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service->update(array_filter(
            $data,
            fn ($value) => $value !== null
        ));

        $service->load('salon');

        return response()->json([
            'data' => $service,
            'message' => 'خدمت با موفقیت ویرایش شد',
        ]);
    }

    public function delete(int $service_id)
    {
        $service = Service::with('salon')->find($service_id);

        if (!$service) {
            return response()->json([
                'data' => null,
                'message' => 'خدمت پیدا نشد',
            ], 404);
        }

        $service->delete();

        return response()->json([
            'data' => $service,
            'message' => 'خدمت با موفقیت حذف شد',
        ]);
    }
}