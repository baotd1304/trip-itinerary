<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripReopenRequest;
use App\Notifications\ReopenRequestReviewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TripReopenReviewController extends Controller
{
    /** Advisor/Admin DUYỆT -> trip chuyển sang editing */
    public function approve(Request $request, TripReopenRequest $reopenRequest)
    {
        Gate::authorize('review', $reopenRequest);

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($reopenRequest, $request, $data) {
            $trip = Trip::whereKey($reopenRequest->trip_id)->lockForUpdate()->firstOrFail();

            abort_unless(
                $trip->status === Trip::STATUS_CONFIRMED,
                409,
                'Trạng thái chuyến vừa thay đổi, vui lòng tải lại trang.'
            );

            $reopenRequest->approve($request->user(), $data['review_note'] ?? null);
            $trip->markEditing();
        });

        $reopenRequest->requester?->notify(new ReopenRequestReviewed($reopenRequest->refresh()));

        return back(303)->with(
            'success',
            "Đã mở khoá chuyến #{$reopenRequest->trip_id}. Tài xế có thể chỉnh sửa."
        );
    }

    /** Advisor/Admin TỪ CHỐI -> trip giữ nguyên confirmed */
    public function reject(Request $request, TripReopenRequest $reopenRequest)
    {
        Gate::authorize('review', $reopenRequest);

        $data = $request->validate([
            'review_note' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'review_note.required' => 'Vui lòng nêu lý do từ chối yêu cầu.',
        ]);

        $reopenRequest->reject($request->user(), $data['review_note']);

        $reopenRequest->requester?->notify(new ReopenRequestReviewed($reopenRequest->refresh()));

        return back(303)->with('success', 'Đã từ chối yêu cầu mở khoá.');
    }
}