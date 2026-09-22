<?php

namespace App\Policies;

use App\Models\TripReopenRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TripReopenRequestPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole('admin') ? true : null;
    }

    public function view(User $user, TripReopenRequest $request): bool
    {
        return $request->requested_by === $user->id
            || $request->trip->advisor_id === $user->id;
    }

    /** Duyệt hoặc từ chối yêu cầu */
    public function review(User $user, TripReopenRequest $request): Response
    {
        if (! $user->hasRole('advisor')) {
            return Response::deny('Chỉ cố vấn phụ trách hoặc quản trị viên mới được duyệt yêu cầu.');
        }

        if ($request->trip->advisor_id !== $user->id) {
            return Response::deny('Bạn không phải cố vấn phụ trách chuyến này.');
        }

        return $request->isPending()
            ? Response::allow()
            : Response::deny('Yêu cầu này đã được xử lý trước đó.');
    }

    /** Driver tự huỷ yêu cầu chưa được duyệt */
    public function cancel(User $user, TripReopenRequest $request): Response
    {
        if ($request->requested_by !== $user->id) {
            return Response::deny('Bạn không phải người tạo yêu cầu này.');
        }

        return $request->isPending()
            ? Response::allow()
            : Response::deny('Yêu cầu đã được xử lý, không thể huỷ.');
    }
}