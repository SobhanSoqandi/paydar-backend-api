<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'data' => null,
                'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید',
            ], 401);
        }

        $jwt = app(JwtService::class);
        $payload = $jwt->decodeToken($token);

        if (!$payload) {
            return response()->json([
                'data' => null,
                'message' => 'توکن نامعتبر است',
            ], 401);
        }

        if (($payload['type'] ?? null) !== 'access') {
            return response()->json([
                'data' => null,
                'message' => 'توکن نامعتبر است',
            ], 401);
        }

        $request->attributes->set('auth_user', $payload);

        return $next($request);
    }
}