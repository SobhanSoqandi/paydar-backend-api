<?php

namespace App\Http\Controllers\Discount;

use App\Models\Customer;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::with([
            'customer',
            'discountUsages',
        ])->get();

        return response()->json([
            'data' => $discounts,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function me(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');

        $customer = Customer::where('user_id', $authUser['sub'] ?? null)->first();

        if (!$customer) {
            return response()->json([
                'detail' => 'مشتری پیدا نشد',
            ], 404);
        }

        $discounts = Discount::with([
            'discountUsages',
        ])
            ->where('customer_id', $customer->id)
            ->get();

        return response()->json([
            'data' => $discounts,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:255',
            'percent' => 'required|numeric|min:0|max:100',
            'customer_id' => 'required|integer|exists:customers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'max_usage' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $startDate = !empty($data['start_date'])
            ? $data['start_date']
            : now();

        $endDate = !empty($data['end_date'])
            ? $data['end_date']
            : \Carbon\Carbon::parse($startDate)->addDays(30);

        $discount = Discount::create([
            'title' => $data['title'],
            'percent' => $data['percent'],
            'customer_id' => $data['customer_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_usage' => $data['max_usage'] ?? 1,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $discount,
            'message' => 'تخفیف با موفقیت ایجاد شد',
        ], 201);
    }

    public function show(int $discount_id)
    {
        $discount = Discount::with([
            'customer',
            'discountUsages',
        ])->find($discount_id);

        if (!$discount) {
            return response()->json([
                'detail' => 'تخفیف پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $discount,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function update(Request $request, int $discount_id)
    {
        $discount = Discount::find($discount_id);

        if (!$discount) {
            return response()->json([
                'detail' => 'تخفیف پیدا نشد',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|min:1|max:255',
            'percent' => 'sometimes|numeric|min:0|max:100',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'max_usage' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $discount->update($validator->validated());

        return response()->json([
            'data' => $discount->fresh(),
            'message' => 'تخفیف با موفقیت ایجاد شد',
        ]);
    }

    public function destroy(int $discount_id)
    {
        $discount = Discount::find($discount_id);

        if (!$discount) {
            return response()->json([
                'detail' => 'تخفیف پیدا نشد',
            ], 404);
        }

        $discount->delete();

        return response()->json([
            'data' => null,
            'message' => 'تخفیف با موفقیت حذف شد',
        ]);
    }
}