<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Notifications\TripReopened;
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
            'reopen_reason.required' => 'Vui lòng nhập lý do yêu cầu tài xế sửa lại.',
            'reopen_reason.min'      => 'Lý do phải có ít nhất 5 ký tự.',
            'reopen_reason.max'      => 'Lý do tối đa 500 ký tự.',
        ]);

        DB::transaction(function () use ($trip, $data, $request) {
            // Khoá dòng để tránh 2 người cùng đổi trạng thái
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            // Kiểm tra lại trạng thái sau khi khoá (chống race condition)
            abort_unless($fresh->isReopenable(), 409, 'Trạng thái chuyến vừa thay đổi, vui lòng tải lại trang.');

            $fresh->reopenFor($request->user(), $data['reopen_reason']);
        });

        // (Tuỳ chọn) báo cho tài xế
        $trip->driver?->notify(new TripReopened($trip->refresh()));

        return back(303)->with(
            'success',
            "Đã mở khoá chỉnh sửa cho chuyến #{$trip->id}. Tài xế có thể cập nhật lại."
        );
    }
}