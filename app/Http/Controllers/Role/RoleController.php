<?php

namespace App\Http\Controllers\Role;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();

        return response()->json([
            'data' => $roles,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => $validator->validated()['name'],
        ]);

        return response()->json([
            'data' => $role,
            'message' => 'با موفقیت ایجاد شد',
        ], 201);
    }

    public function show(int $role_id)
    {
        $role = Role::find($role_id);

        if (!$role) {
            return response()->json([
                'detail' => 'نقش پیدا نشد',
            ], 404);
        }

        return response()->json([
            'data' => $role,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function update(Request $request, int $role_id)
    {
        $role = Role::find($role_id);

        if (!$role) {
            return response()->json([
                'detail' => 'نقش پیدا نشد',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles,name,' . $role_id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => $validator->errors(),
            ], 422);
        }

        $role->update($validator->validated());

        return response()->json([
            'data' => $role->fresh(),
            'message' => 'با موفقیت ویرایش شد',
        ]);
    }

    public function destroy(int $role_id)
    {
        $role = Role::find($role_id);

        if (!$role) {
            return response()->json([
                'detail' => 'نقش پیدا نشد',
            ], 404);
        }

        $role->delete();

        return response()->json([
            'data' => null,
            'message' => 'با موفقیت حذف شد',
        ]);
    }
}