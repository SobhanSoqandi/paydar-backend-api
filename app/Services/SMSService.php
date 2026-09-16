<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SMSService
{
    public function send(string $phoneNumber, string $message): array
    {
        // $apiKey = env('FARAZ_SMS_API_KEY');
        $apiKey = config('services.faraz_sms.api_key');

        if (!$apiKey) {
            throw new RuntimeException('FARAZ_SMS_API_KEY تنظیم نشده است.');
        }

        $response = Http::withHeaders([
            'Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(10)
            ->post('https://api.iranpayamak.com/ws/v1/sms/simple', [
                'text' => $message,
                'line_number' => '90008361',
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
}