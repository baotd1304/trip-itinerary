<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Notifications\TripReopened;
use App\Support\Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TripReopenController extends Controller
{
    public function __invoke(Request $request, Trip $trip)
    {
        Gate::authorize('reopen', $trip);

        $data = $request->validate([
            'reopen_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reopen_reason.required' => 'trip.validation.reopen_reason_required',
            'reopen_reason.min' => 'trip.validation.reopen_reason_min',
            'reopen_reason.max' => 'trip.validation.reopen_reason_max',
        ]);

        DB::transaction(function () use ($trip, $data, $request) {
            // Khoá dòng để tránh 2 người cùng đổi trạng thái
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            // Kiểm tra lại trạng thái sau khi khoá (chống race condition)
            abort_unless($fresh->isReopenable(), 409, 'trip.conflicts.trip_state_changed');

            $fresh->reopenFor($request->user(), $data['reopen_reason']);
        });

        // (Tuỳ chọn) báo cho tài xế
        $trip->driver?->notify(new TripReopened($trip->refresh()));

        return back(303)->with('flash', Flash::success('trip.flash.tripReopened', [
            'id' => $trip->id,
        ]));
    }
}
