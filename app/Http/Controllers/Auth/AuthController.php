<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly JwtService $jwt
    ) {}

    /**
     * ورود کاربر و صدور access_token و refresh_token
     */
  
    public function login(Request $request): JsonResponse
{
    $validated = $request->validate([
        'phone_number' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('phone', $validated['phone_number'])->first();

    if (!$user) {
        return response()->json([
            'data' => null,
            'message' => 'نام کاربری یا رمز عبور اشتباه است',
        ], 400);
    }

    $passwordHash = str_replace('$2b$', '$2y$', $user->password_hash);

    if (!Hash::check($validated['password'], $passwordHash)) {
        return response()->json([
            'data' => null,
            'message' => 'نام کاربری یا رمز عبور اشتباه است',
        ], 400);
    }

    $role = $user->role?->name;

    $refreshToken = $this->createRefreshToken($user->id, $role);

    $accessToken = $this->jwt->createToken([
        'sub' => (string) $user->id,
        'type' => 'access',
        'role' => $role,
    ]);

    return response()->json([
        'data' => [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ],
        'message' => 'ورود با موفقیت انجام شد',
    ]);
}

    /**
     * خروج کاربر و باطل کردن refresh_token
     */
    public function logout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $token = Token::where('refresh_token', $validated['refresh_token'])
            ->where('is_revoked', false)
            ->first();

        if (!$token) {
            return response()->json([
                'data' => null,
                'message' => 'invalid refresh token',
            ], 401);
        }

        $token->update(['is_revoked' => true]);

        return response()->json([
            'data' => null,
            'message' => 'خروج با موفقیت انجام شد',
        ]);
    }

    /**
     * بروزرسانی access_token با استفاده از refresh_token
     */
    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $token = Token::where('refresh_token', $validated['refresh_token'])
            ->where('is_revoked', false)
            ->first();

        if (!$token) {
            return response()->json([
                'data' => null,
                'message' => 'invalid refresh token',
            ], 401);
        }

        $payload = $this->jwt->decodeToken($token->refresh_token);

        if (!$payload) {
            return response()->json([
                'data' => null,
                'message' => 'invalid refresh token',
            ], 401);
        }

        // باطل کردن توکن قدیمی (rotation)
        $token->update(['is_revoked' => true]);

        $userId = (int) $payload['sub'];
        $role = $payload['role'] ?? null;

        $newRefreshToken = $this->createRefreshToken($userId, $role);

        $accessToken = $this->jwt->createToken([
            'sub' => (string) $userId,
            'role' => $role,
            'type' => 'access',
        ]);

        return response()->json([
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
            ],
            'message' => 'توکن با موفقیت بروزرسانی شد',
        ]);
    }

    /**
     * ساخت refresh_token جدید یا برگرداندن توکن فعال موجود
     */
    private function createRefreshToken(int $userId, ?string $role): string
    {
        $activeToken = Token::where('user_id', $userId)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_revoked', false)
            ->first();

        if ($activeToken) {
            return $activeToken->refresh_token;
        }

        $refreshToken = $this->jwt->createToken([
            'sub' => (string) $userId,
            'type' => 'refresh',
            'role' => $role,
        ], 5 * 24 * 60);

        Token::create([
            'user_id' => $userId,
            'refresh_token' => $refreshToken,
            'expires_at' => Carbon::now()->addDays(5),
            'is_revoked' => false,
        ]);

        return $refreshToken;
    }
}