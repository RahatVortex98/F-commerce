<?php

namespace App\Services;

class BangladeshPhoneNumber
{
    /**
     * @return array{local: string, e164: string}|null
     */
    public function normalize(string $phone): ?array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (preg_match('/^8801[3-9]\d{8}$/', $digits) === 1) {
            $localNumber = '0'.substr($digits, 3);
        } elseif (preg_match('/^01[3-9]\d{8}$/', $digits) === 1) {
            $localNumber = $digits;
        } else {
            return null;
        }

        return [
            'local' => $localNumber,
            'e164' => '+88'.substr($localNumber, 1),
        ];
    }
}
