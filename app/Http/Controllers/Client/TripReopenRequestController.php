<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripReopenRequest;
use App\Notifications\ReopenRequestSubmitted;
use App\Support\Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TripReopenRequestController extends Controller
{
    /** Driver gửi yêu cầu mở khoá chuyến đã confirmed */
    public function store(Request $request, Trip $trip)
    {
        Gate::authorize('requestReopen', $trip);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'reason.required' => 'trip.validation.reopen_request_reason_required',
            'reason.min' => 'trip.validation.reopen_request_reason_min',
            'reason.max' => 'trip.validation.reopen_request_reason_max',
        ]);

        $reopenRequest = DB::transaction(function () use ($trip, $data, $request) {
            // Khoá dòng trip, chống double-submit tạo 2 yêu cầu
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                $fresh->canRequestReopen(),
                409,
                'trip.conflicts.trip_state_changed'
            );

            $duplicated = TripReopenRequest::where('trip_id', $fresh->id)
                ->where('status', TripReopenRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            abort_if($duplicated, 409, 'trip.conflicts.duplicate_reopen_request');

            return TripReopenRequest::create([
                'trip_id' => $fresh->id,
                'requested_by' => $request->user()->id,
                'reason' => $data['reason'],
                'status' => TripReopenRequest::STATUS_PENDING,
            ]);
        });

        $trip->advisor?->notify(new ReopenRequestSubmitted($reopenRequest));

        return back(303)->with('flash', Flash::success('trip.flash.reopenRequested', [
            'id' => $trip->id,
        ]));
    }

    /** Driver huỷ yêu cầu của chính mình khi chưa được duyệt */
    public function destroy(TripReopenRequest $reopenRequest)
    {
        Gate::authorize('cancel', $reopenRequest);

        $reopenRequest->delete();

        return back(303)->with('flash', Flash::success('trip.flash.reopenCancelled'));
    }
}
