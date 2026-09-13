<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $authUser = $request->attributes->get('auth_user');

        if (!$authUser || !isset($authUser['sub'])) {
            return response()->json([
                'detail' => 'توکن نامعتبر است',
            ], 401);
        }

        $user = User::with('role')->find($authUser['sub']);

        if (!$user) {
            return response()->json([
                'detail' => 'کاربر پیدا نشد',
            ], 404);
        }

        if (!$user->role) {
            return response()->json([
                'detail' => 'شما به این روت دسترسی ندارید ',
            ], 403);
        }

        if (!in_array($user->role->name, $roles, true)) {
            return response()->json([
                'detail' => 'شما به این روت دسترسی ندارید ',
            ], 403);
        }

        $request->attributes->set('current_user', $user);

        return $next($request);
    }
}