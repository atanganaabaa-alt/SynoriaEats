<?php

namespace App\Services\Notifications;

use Illuminate\Support\Str;

class PhoneNumber
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (Str::startsWith($digits, '237')) {
            return '+'.$digits;
        }

        if (Str::startsWith($digits, '0')) {
            return '+237'.substr($digits, 1);
        }

        if (Str::length($digits) === 9) {
            return '+237'.$digits;
        }

        return Str::startsWith($phone, '+') ? $phone : '+'.$digits;
    }
}
