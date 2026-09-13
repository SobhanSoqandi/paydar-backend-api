<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;

class JwtService
{
    private string $secret;
    private string $algorithm;

    public function __construct()
    {
        $this->secret = config('app.jwt_secret');
        $this->algorithm = config('app.jwt_algorithm', 'HS256');

        if (empty($this->secret)) {
            throw new Exception(
                'JWT secret key (SECRET_KEY) is not configured in .env or config/app.php.'
            );
        }
    }

    public function createToken(array $data, ?int $expireMinutes = null): string
    {
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT',
        ];

        $payload = $data;

        $minutes = $expireMinutes
            ?? (int) config('app.access_token_expire_minutes', 30);

        $payload['exp'] = Carbon::now('UTC')
            ->addMinutes($minutes)
            ->timestamp;

        $encodedHeader = $this->base64UrlEncode(
            json_encode($header, JSON_UNESCAPED_SLASHES)
        );

        $encodedPayload = $this->base64UrlEncode(
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        $signature = hash_hmac(
            'sha256',
            $encodedHeader . '.' . $encodedPayload,
            $this->secret,
            true
        );

        return $encodedHeader . '.' .
            $encodedPayload . '.' .
            $this->base64UrlEncode($signature);
    }

    public function decodeToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

            $headerJson = $this->base64UrlDecode($encodedHeader);
            $payloadJson = $this->base64UrlDecode($encodedPayload);
            $signature = $this->base64UrlDecode($encodedSignature);

            if ($headerJson === false || $payloadJson === false || $signature === false) {
                return null;
            }

            $header = json_decode($headerJson, true);
            $payload = json_decode($payloadJson, true);

            if (!is_array($header) || !is_array($payload)) {
                return null;
            }

            if (($header['alg'] ?? null) !== $this->algorithm) {
                return null;
            }

            $expectedSignature = hash_hmac(
                'sha256',
                $encodedHeader . '.' . $encodedPayload,
                $this->secret,
                true
            );

            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            if (
                isset($payload['exp']) &&
                $payload['exp'] < Carbon::now('UTC')->timestamp
            ) {
                return null;
            }

            return $payload;

        } catch (\Throwable $e) {
            return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }

    private function base64UrlDecode(string $data): string|false
    {
        $data = strtr($data, '-_', '+/');

        $padding = strlen($data) % 4;

        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($data, true);
    }
}