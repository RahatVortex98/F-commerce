<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;

class ConfiguredSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        $driver = config('sms.driver');

        if ($driver === 'log') {
            if (! app()->environment(['local', 'testing'])) {
                throw new LogicException('The log SMS driver is only available in local and testing environments.');
            }

            Log::info('Local phone verification SMS', [
                'phone' => $phone,
                'message' => $message,
            ]);

            return;
        }

        if ($driver !== 'http_json') {
            throw new LogicException('The configured SMS driver is not supported.');
        }

        $endpoint = config('sms.endpoint');

        if (! $endpoint) {
            throw new LogicException('Configure an SMS API endpoint before accepting phone verification requests.');
        }

        $headers = [];
        $token = config('sms.token');
        $headerName = config('sms.auth_header');

        if ($token && $headerName) {
            $headers[$headerName] = config('sms.auth_prefix').$token;
        }

        Http::connectTimeout(3)
            ->timeout(8)
            ->withHeaders($headers)
            ->post($endpoint, [
                config('sms.phone_field', 'to') => $phone,
                config('sms.message_field', 'message') => $message,
            ])
            ->throw();
    }
}
