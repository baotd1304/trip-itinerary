<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TripImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class CloudinaryUploadController extends Controller
{
    public function signature(CloudinaryService $cloudinary)
    {
        return response()->json($cloudinary->uploadSignature());
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