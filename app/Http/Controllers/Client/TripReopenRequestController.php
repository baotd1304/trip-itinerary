<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripReopenRequest;
use App\Notifications\ReopenRequestSubmitted;
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
            'reason.required' => 'Vui lòng nhập lý do cần chỉnh sửa lại chuyến.',
            'reason.min'      => 'Lý do phải có ít nhất 10 ký tự để cố vấn hiểu rõ.',
            'reason.max'      => 'Lý do tối đa 500 ký tự.',
        ]);

        $reopenRequest = DB::transaction(function () use ($trip, $data, $request) {
            // Khoá dòng trip, chống double-submit tạo 2 yêu cầu
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                $fresh->canRequestReopen(),
                409,
                'Trạng thái chuyến vừa thay đổi, vui lòng tải lại trang.'
            );

            $duplicated = TripReopenRequest::where('trip_id', $fresh->id)
                ->where('status', TripReopenRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            abort_if($duplicated, 409, 'Đã có yêu cầu mở khoá đang chờ duyệt cho chuyến này.');

            return TripReopenRequest::create([
                'trip_id'      => $fresh->id,
                'requested_by' => $request->user()->id,
                'reason'       => $data['reason'],
                'status'       => TripReopenRequest::STATUS_PENDING,
            ]);
        });

        $trip->advisor?->notify(new ReopenRequestSubmitted($reopenRequest));

        return back(303)->with(
            'success',
            "Đã gửi yêu cầu mở khoá chuyến #{$trip->id}. Vui lòng chờ cố vấn duyệt."
        );
    }

    /** Driver huỷ yêu cầu của chính mình khi chưa được duyệt */
    public function destroy(TripReopenRequest $reopenRequest)
    {
        Gate::authorize('cancel', $reopenRequest);

        $reopenRequest->delete();

        return back(303)->with('success', 'Đã huỷ yêu cầu mở khoá.');
    }
}