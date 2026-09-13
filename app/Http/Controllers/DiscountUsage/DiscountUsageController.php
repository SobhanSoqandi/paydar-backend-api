<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountUsageController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'discount_id' => 'required|integer|exists:discounts,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'appointment_id' => 'required|integer|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $discount = Discount::find($data['discount_id']);

        if (!$discount) {
            return response()->json([
                'detail' => 'تخفیف پیدا نشد',
            ], 404);
        }

        $usage = DiscountUsage::create([
            'discount_id' => $data['discount_id'],
            'customer_id' => $data['customer_id'],
            'appointment_id' => $data['appointment_id'],
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => $usage,
            'message' => 'استفاده از تخفیف با موفقیت ثبت شد',
        ], 201);
    }
}