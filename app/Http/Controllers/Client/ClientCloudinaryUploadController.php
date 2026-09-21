<?php 

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\TripImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class ClientCloudinaryUploadController extends Controller
{
    public function signature(Request $request, CloudinaryService $cloudinary)
    {
        $validated = $request->validate([
            'count' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        return response()->json(
            $cloudinary->uploadSlots((int) ($validated['count'] ?? 1))
        );
    }

    /** Xoá ảnh đã upload nhưng user bấm Cancel (ảnh mồ côi) */
    public function discard(Request $request, CloudinaryService $cloudinary)
    {
        $data = $request->validate([
            'public_ids'   => ['required', 'array', 'max:20'],
            'public_ids.*' => ['string', 'max:255'],
        ]);

        foreach ($data['public_ids'] as $publicId) {
            // chỉ xoá nếu chưa được gán vào trip nào
            if (! TripImage::where('public_id', $publicId)->exists()) {
                $cloudinary->destroy($publicId);
            }
        }

        return response()->noContent();
    }
}