<?php

namespace App\Http\Controllers\WalletTransaction;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletTransactionController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|integer|exists:wallets,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|string|in:spend,paid,cashback',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction = WalletTransaction::create($validated);

        return response()->json([
            'data' => $transaction,
            'message' => 'تراکنش با موفقیت ایجاد شد',
        ], 201);
    }

    public function customer(Request $request)
    {
        try {
            $payload = $request->attributes->get('auth_user');

            $userId = $payload['sub'] ?? null;

            $customer = Customer::where('user_id', $userId)->first();

            if (!$customer) {
                return response()->json([
                    'data' => [],
                    'message' => 'اطلاعات مشتری یافت نشد',
                ], 404);
            }

            $wallet = Wallet::where(
                'customer_id',
                $customer->id
            )->first();

            if (!$wallet) {
                return response()->json([
                    'data' => [],
                    'message' => 'کیف پول یافت نشد',
                ], 404);
            }

            $transactions = WalletTransaction::where(
                'wallet_id',
                $wallet->id
            )
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'data' => $transactions,
                'message' => 'تراکنش‌ها با موفقیت دریافت شد',
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'data' => null,
                'message' => 'خطای داخلی سرور',
            ], 500);
        }
    }
}