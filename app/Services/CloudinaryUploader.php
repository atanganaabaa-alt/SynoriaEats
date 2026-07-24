<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CloudinaryUploader
{
    /**
     * Upload an image to Cloudinary (URL only stored in DB).
     * Falls back to local public disk if Cloudinary is not configured.
     */
    public function upload(UploadedFile $file, string $folder = 'menu-items'): string
    {
        if ($this->isConfigured()) {
            return $this->uploadToCloudinary($file, $folder);
        }

        Log::info('Cloudinary non configuré — stockage local public.');

        return $file->store($folder, 'public');
    }

    public function isConfigured(): bool
    {
        return filled(config('services.cloudinary.cloud_name'))
            && (
                filled(config('services.cloudinary.upload_preset'))
                || (filled(config('services.cloudinary.key')) && filled(config('services.cloudinary.secret')))
            );
    }

    private function uploadToCloudinary(UploadedFile $file, string $folder): string
    {
        $cloud = config('services.cloudinary.cloud_name');
        $endpoint = "https://api.cloudinary.com/v1_1/{$cloud}/image/upload";

        $payload = [
            'folder' => trim((string) config('services.cloudinary.folder', 'synoriaeats'), '/').'/'.$folder,
        ];

        if (filled(config('services.cloudinary.upload_preset'))) {
            $payload['upload_preset'] = config('services.cloudinary.upload_preset');
        } else {
            $timestamp = time();
            $paramsToSign = [
                'folder' => $payload['folder'],
                'timestamp' => $timestamp,
            ];
            ksort($paramsToSign);
            $toSign = collect($paramsToSign)
                ->map(fn ($v, $k) => $k.'='.$v)
                ->implode('&');
            $payload['api_key'] = config('services.cloudinary.key');
            $payload['timestamp'] = $timestamp;
            $payload['signature'] = sha1($toSign.config('services.cloudinary.secret'));
        }

        $response = Http::asMultipart()
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Échec upload Cloudinary : '.$response->body());
        }

        $url = $response->json('secure_url') ?? $response->json('url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Cloudinary n’a pas renvoyé d’URL.');
        }

        return $url;
    }

    public function deleteIfLocal(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
