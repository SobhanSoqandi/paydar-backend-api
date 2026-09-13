<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $payload = $request->attributes->get('auth_user');

        if (!$payload || empty($payload['sub'])) {
            return response()->json([
                'data' => null,
                'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید',
            ], 401);
        }

        $user = User::with('role')->find($payload['sub']);

        if (!$user || !$user->role || strtolower($user->role->name) !== strtolower($role)) {
            return response()->json([
                'data' => null,
                'message' => 'شما به این روت دسترسی ندارید ',
            ], 403);
        }

        $request->attributes->set('auth_model', $user);

        return $next($request);
    }
}