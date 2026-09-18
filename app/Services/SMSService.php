<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SMSService
{
    public function send(string $phoneNumber, string $message): array
    {
        $apiKey = config('services.faraz_sms.api_key');
        $lineNumber = config('services.faraz_sms.line_number');

        if (!$apiKey) {
            throw new RuntimeException('FARAZ_SMS_API_KEY تنظیم نشده است.');
        }

        if (!$lineNumber) {
            throw new RuntimeException('FARAZ_SMS_LINE_NUMBER تنظیم نشده است.');
        }

        $response = Http::withHeaders([
            'Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(10)
            ->post('https://api.iranpayamak.com/ws/v1/sms/simple', [
                'text' => $message,
                'line_number' => $lineNumber,
                'recipients' => [$phoneNumber],
                'number_format' => 'persian',
                'schedule' => null,
            ]);

        if (!$response->successful()) {
            Log::error('Faraz SMS failed', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException(
                'ارسال پیامک با خطا مواجه شد.'
            );
        }

        return $response->json();
    }

    public function sendPattern(string $phoneNumber, array $attributes): array
    {
        $apiKey = config('services.faraz_sms.api_key');
        $lineNumber = config('services.faraz_sms.line_number');
        $patternCode = config('services.faraz_sms.password_pattern');

        if (!$apiKey) {
            throw new RuntimeException('FARAZ_SMS_API_KEY تنظیم نشده است.');
        }

        if (!$lineNumber) {
            throw new RuntimeException('FARAZ_SMS_LINE_NUMBER تنظیم نشده است.');
        }

        if (!$patternCode) {
            throw new RuntimeException('FARAZ_SMS_PASSWORD_PATTERN تنظیم نشده است.');
        }

        $response = Http::withHeaders([
            'Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(10)
            ->post('https://api.iranpayamak.com/ws/v1/sms/pattern', [
                'code' => $patternCode,
                'attributes' => $attributes,
                'recipient' => $phoneNumber,
                'line_number' => $lineNumber,
                'number_format' => 'english',
            ]);

        if (!$response->successful()) {
            Log::error('Faraz Pattern SMS failed', [
                'phone' => $phoneNumber,
                'pattern_code' => $patternCode,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException(
                'ارسال پیامک پترن با خطا مواجه شد.'
            );
        }

        return $response->json();
    }

    public function sendPassword(string $phoneNumber, string $password): array
    {
        return $this->sendPattern($phoneNumber, [
            'pass' => $password,
        ]);
    }
}