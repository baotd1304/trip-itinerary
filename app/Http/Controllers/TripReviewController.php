<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Notifications\TripReviewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TripReviewController extends Controller
{
    public function confirm(Request $request, Trip $trip)
    {
        Gate::authorize('review', $trip);

        DB::transaction(function () use ($trip, $request) {
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            abort_unless($fresh->isReviewable(), 409, 'Chuyến không còn ở trạng thái chờ duyệt.');

            $fresh->markConfirmed($request->user());
        });

        $trip->driver?->notify(new TripReviewed($trip->refresh()));

        return back(303)->with('success', "Đã xác nhận chuyến #{$trip->id}.");
    }

    public function reject(Request $request, Trip $trip)
    {
        Gate::authorize('review', $trip);

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reject_reason.required' => 'Vui lòng nêu lý do từ chối để tài xế biết cần sửa gì.',
        ]);

        DB::transaction(function () use ($trip, $request, $data) {
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            abort_unless($fresh->isReviewable(), 409, 'Chuyến không còn ở trạng thái chờ duyệt.');

            $fresh->markRejected($request->user(), $data['reject_reason']);
        });

        $trip->driver?->notify(new TripReviewed($trip->refresh()));

        return back(303)->with('success', "Đã từ chối chuyến #{$trip->id}. Tài xế có thể chỉnh sửa lại.");
    }
}