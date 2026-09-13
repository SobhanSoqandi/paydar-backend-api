<?php

namespace App\Http\Controllers\AppointmentService;

use App\Http\Controllers\Controller;
use App\Models\AppointmentService;
use Illuminate\Http\Request;

class AppointmentServiceController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $item = AppointmentService::create($data);

        $item->load('service');

        return response()->json([
            'data' => $item,
            'message' => 'خدمت نوبت با موفقیت ایجاد شد',
        ]);
    }

    public function index()
    {
        $items = AppointmentService::with('service')->get();

        return response()->json([
            'data' => $items,
            'message' => 'لیست خدمات نوبت دریافت شد',
        ]);
    }

    public function show(int $id)
    {
        $item = AppointmentService::with('service')->find($id);

        if (!$item) {
            return response()->json([
                'data' => null,
                'message' => 'رکورد پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $item,
            'message' => 'اطلاعات دریافت شد',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $item = AppointmentService::find($id);

        if (!$item) {
            return response()->json([
                'data' => null,
                'message' => 'رکورد پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);

        $item->update(
            array_filter(
                $data,
                fn ($value) => $value !== null
            )
        );

        $item->load('service');

        return response()->json([
            'data' => $item,
            'message' => 'رکورد با موفقیت ویرایش شد',
        ]);
    }

    public function delete(int $id)
    {
        $item = AppointmentService::find($id);

        if (!$item) {
            return response()->json([
                'data' => null,
                'message' => 'رکورد پیدا نشد',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'data' => $item,
            'message' => 'رکورد با موفقیت حذف شد',
        ]);
    }
}