<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->cloudName = (string) config('services.cloudinary.cloud_name');
        $this->apiKey    = (string) config('services.cloudinary.api_key');
        $this->apiSecret = (string) config('services.cloudinary.api_secret');
    }

    /** Ký theo chuẩn Cloudinary: sort key -> k=v&k=v -> nối api_secret -> sha1 */
    public function sign(array $params): string
    {
        ksort($params);

        $toSign = collect($params)
            ->reject(fn ($v) => $v === null || $v === '')
            ->map(fn ($v, $k) => $k.'='.$v)
            ->implode('&');

        return sha1($toSign.$this->apiSecret);
    }

    /**
     * Tạo N "slot" upload, mỗi slot có folder theo ngày + tên file duy nhất.
     */
    public function uploadSlots(int $count = 1): array
    {
        $count = max(1, min($count, 10));
        $now   = now();

        $base      = trim((string) config('services.cloudinary.folder', 'trip_iti'), '/');
        $folder    = $base.'/'.'trips/'.$now->format('Y/m/d');   // trips/2026/08/31
        $timestamp = $now->getTimestamp();

        $slots = [];

        for ($i = 0; $i < $count; $i++) {
            // 20260831_105430_a7f3k9qz  -> theo giây + 8 ký tự ngẫu nhiên => không trùng
            $publicId = $now->format('Ymd_His').'_'.Str::lower(Str::random(8));

            $params = [
                'folder'          => $folder,
                'public_id'       => $publicId,
                'overwrite'       => 'false',
                'unique_filename' => 'false',
                'use_filename'    => 'false',
                'timestamp'       => $timestamp,
            ];

            $slots[] = $params + ['signature' => $this->sign($params)];
        }

        return [
            'cloud_name' => $this->cloudName,
            'api_key'    => $this->apiKey,
            'upload_url' => "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload",
            'slots'      => $slots,
        ];
    }

    /** Không bao giờ ném exception -> an toàn khi gọi hàng loạt */
    public function destroy(string $publicId): bool
    {
        $timestamp = time();

        try {
            $response = Http::asForm()->timeout(15)->post(
                "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy",
                [
                    'public_id' => $publicId,
                    'timestamp' => $timestamp,
                    'api_key'   => $this->apiKey,
                    'signature' => $this->sign([
                        'public_id' => $publicId,
                        'timestamp' => $timestamp,
                    ]),
                ]
            );
        } catch (Throwable $e) {
            report($e);
            return false;
        }

        return $response->successful()
            && in_array(data_get($response->json(), 'result'), ['ok', 'not found'], true);
    }
}