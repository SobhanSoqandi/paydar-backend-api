<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'balance' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $wallet = Wallet::create([
            'customer_id' => $data['customer_id'],
            'balance' => $data['balance'] ?? 0,
        ]);

        return response()->json([
            'data' => $wallet,
            'message' => 'با موفقیت ایجاد شد',
        ], 201);
    }

    public function customer(int $customer_id)
    {
        $wallet = Wallet::with('customer')
            ->where('customer_id', $customer_id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'detail' => 'کیف پول مورد نظر پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $wallet,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function show(int $wallet_id)
    {
        $wallet = Wallet::with([
            'customer',
            'transactions',
        ])->find($wallet_id);

        if (!$wallet) {
            return response()->json([
                'detail' => 'کیف پول مورد نظر پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $wallet,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function update(Request $request, int $wallet_id)
    {
        $wallet = Wallet::find($wallet_id);

        if (!$wallet) {
            return response()->json([
                'detail' => 'کیف پول مورد نظر پیدا نشد',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'balance' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $wallet->update($validator->validated());

        return response()->json([
            'data' => $wallet->fresh(),
            'message' => 'با موفقیت ویرایش شد',
        ]);
    }

    public function delete(int $wallet_id)
    {
        $wallet = Wallet::find($wallet_id);

        if (!$wallet) {
            return response()->json([
                'detail' => 'کیف پول مورد نظر پیدا نشد',
            ], 404);
        }

        $wallet->delete();

        return response()->json([
            'data' => null,
            'message' => 'با موفقیت حذف شد',
        ]);
    }
}