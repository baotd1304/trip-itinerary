<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkTripReviewRequest;
use App\Models\Trip;
use App\Notifications\TripReviewed;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
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

            abort_unless($fresh->isReviewable(), 409, 'trip.conflicts.trip_not_reviewable');

            $fresh->markConfirmed($request->user());
        });

        $trip->driver?->notify(new TripReviewed($trip->refresh()));

        return back(303)->with('flash', Flash::success('trip.flash.reviewConfirmed', [
            'id' => $trip->id,
        ]));
    }

    public function reject(Request $request, Trip $trip)
    {
        Gate::authorize('review', $trip);

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reject_reason.required' => 'trip.validation.trip_reject_reason_required',
            'reject_reason.min' => 'trip.validation.trip_reject_reason_min',
            'reject_reason.max' => 'trip.validation.trip_reject_reason_max',
        ]);

        DB::transaction(function () use ($trip, $request, $data) {
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            abort_unless($fresh->isReviewable(), 409, 'trip.conflicts.trip_not_reviewable');

            $fresh->markRejected($request->user(), $data['reject_reason']);
        });

        $trip->driver?->notify(new TripReviewed($trip->refresh()));

        return back(303)->with('flash', Flash::success('trip.flash.reviewRejected', [
            'id' => $trip->id,
        ]));
    }

    public function bulkReview(BulkTripReviewRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $trips = DB::transaction(function () use ($data, $request) {
            $trips = Trip::query()
                ->whereKey($data['trip_ids'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_unless($trips->count() === count($data['trip_ids']), 409, 'trip.conflicts.trips_missing');

            foreach ($trips as $trip) {
                Gate::authorize('review', $trip);
                abort_unless($trip->isReviewable(), 409, 'trip.conflicts.trips_not_reviewable');
            }

            foreach ($trips as $trip) {
                if ($data['action'] === 'confirm') {
                    $trip->markConfirmed($request->user());
                } else {
                    $trip->markRejected($request->user(), $data['reject_reason']);
                }
            }

            return $trips;
        });

        $trips->load('driver');

        foreach ($trips as $trip) {
            $trip->driver?->notify(new TripReviewed($trip));
        }

        $flashKey = $data['action'] === 'confirm'
            ? 'trip.flash.bulkReviewConfirmed'
            : 'trip.flash.bulkReviewRejected';

        return back(303)->with('flash', Flash::success($flashKey, [
            'count' => $trips->count(),
        ]));
    }
}
