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

        if (Str::startsWith($url, '/images/') || Str::startsWith($url, 'images/')) {
            return asset(ltrim($url, '/'));
        }

        if (Str::startsWith($url, '/storage/')) {
            return url($url);
        }

        if (Str::startsWith($url, 'storage/')) {
            return asset($url);
        }

        return asset('storage/'.$url);
    }

    /** Vignette plus légère (Cloudinary) ou URL d’origine en local. */
    public static function thumbnail(?string $path, int $width = 480): ?string
    {
        $url = self::resolve($path);

        if ($url === null) {
            return null;
        }

        if (str_contains($url, 'res.cloudinary.com') && str_contains($url, '/upload/')) {
            return (string) preg_replace(
                '#/upload/(?:v\d+/)?#',
                "/upload/w_{$width},q_auto,f_auto,c_limit/",
                $url,
                1
            );
        }

        return $url;
    }
}
