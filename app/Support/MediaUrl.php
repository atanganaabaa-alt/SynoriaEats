<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MediaUrl
{
    public static function resolve(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $url = (string) $path;

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '/storage/')) {
            return url($url);
        }

        if (Str::startsWith($url, 'storage/')) {
            return asset($url);
        }

        return asset('storage/'.$url);
    }
}
