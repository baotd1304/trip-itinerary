<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudinaryService
{
    public function __construct(
        private ?string $cloudName = null,
        private ?string $apiKey = null,
        private ?string $apiSecret = null,
    ) {
        $this->cloudName ??= config('services.cloudinary.cloud_name');
        $this->apiKey    ??= config('services.cloudinary.api_key');
        $this->apiSecret ??= config('services.cloudinary.api_secret');
    }

    /** Ký theo chuẩn Cloudinary: sort key -> k=v&k=v -> nối api_secret -> sha1 */
    public function sign(array $params): string
    {
        ksort($params);

        $toSign = collect($params)
            ->reject(fn ($v) => $v === null || $v === '')
            ->map(fn ($v, $k) => $k.'='.(is_bool($v) ? var_export($v, true) : $v))
            ->implode('&');

        return sha1($toSign.$this->apiSecret);
    }

    /** Payload cho FE upload trực tiếp */
    public function uploadSignature(?string $folder = null): array
    {
        $folder    = $folder ?: config('services.cloudinary.folder');
        $timestamp = time();

        $params = [
            'folder'    => $folder,
            'timestamp' => $timestamp,
        ];

        return [
            'cloud_name' => $this->cloudName,
            'api_key'    => $this->apiKey,
            'timestamp'  => $timestamp,
            'folder'     => $folder,
            'signature'  => $this->sign($params),
            'upload_url' => "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload",
        ];
    }

    public function destroy(string $publicId): bool
    {
        $timestamp = time();

        $response = Http::asForm()->post(
            "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy",
            [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
                'api_key'   => $this->apiKey,
                'signature' => $this->sign(['public_id' => $publicId, 'timestamp' => $timestamp]),
            ]
        );

        return $response->successful()
            && in_array(data_get($response->json(), 'result'), ['ok', 'not found'], true);
    }
}