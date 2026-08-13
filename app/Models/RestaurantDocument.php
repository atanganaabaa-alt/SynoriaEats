<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'restaurant_id',
    'type',
    'url',
    'original_name',
])]
class RestaurantDocument extends Model
{
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * URL ouvrable dans le navigateur (Cloudinary absolue ou /storage/… local).
     */
    public function publicUrl(): string
    {
        $url = (string) $this->url;

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

    public function isRemote(): bool
    {
        return Str::startsWith((string) $this->url, ['http://', 'https://']);
    }

    public function isPdf(): bool
    {
        $name = Str::lower((string) ($this->original_name ?: $this->url));

        return Str::endsWith($name, '.pdf') || Str::contains($name, '.pdf');
    }

    public function isImage(): bool
    {
        $name = Str::lower((string) ($this->original_name ?: $this->url));

        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $name);
    }

    public function localPath(): ?string
    {
        if ($this->isRemote()) {
            return null;
        }

        $path = (string) $this->url;
        $path = Str::of($path)->ltrim('/')->replaceFirst('storage/', '')->toString();

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
