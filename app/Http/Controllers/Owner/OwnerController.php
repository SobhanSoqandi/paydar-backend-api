<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (Owner::where('user_id', $data['user_id'])->exists()) {
            return response()->json([
                'data' => null,
                'message' => 'این مالک قبلاً ثبت شده است',
            ], 403);
        }

        $owner = Owner::create($data);

        $owner->load('user');

        return response()->json([
            'data' => $owner,
            'message' => 'مالک با موفقیت ایجاد شد',
        ]);
    }

    public function index()
    {
        return response()->json([
            'data' => Owner::with('user')->get(),
            'message' => 'لیست مالکان دریافت شد',
        ]);
    }

    public function show(int $owner_id)
    {
        $owner = Owner::with('user')->find($owner_id);

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $owner,
            'message' => 'اطلاعات مالک دریافت شد',
        ]);
    }

    public function update(Request $request, int $owner_id)
    {
        $owner = Owner::find($owner_id);

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $owner->update($data);
        $owner->load('user');

        return response()->json([
            'data' => $owner,
            'message' => 'اطلاعات مالک با موفقیت ویرایش شد',
        ]);
    }

    public function delete(int $owner_id)
    {
        $owner = Owner::with('user')->find($owner_id);

        if (!$owner) {
            return response()->json([
                'data' => null,
                'message' => 'مالک پیدا نشد',
            ], 404);
        }

        $owner->delete();

        return response()->json([
            'data' => $owner,
            'message' => 'مالک با موفقیت حذف شد',
        ]);
    }
}