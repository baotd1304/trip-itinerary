<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TripPolicy
{
    /** Admin toàn quyền */
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['driver', 'advisor', 'editor']);
    }

    public function view(User $user, Trip $trip): bool
    {
        return $trip->driver_id === $user->id || $trip->advisor_id === $user->id;      //chi các user là driver hoặc advisor của chuyến đi mới được xem
    }

    // create: driver, editor được phép tạo chuyến đi
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['driver', 'editor']);
    }

    /* ---------- Driver sửa: chỉ khi editting hoặc rejected ---------- */
    public function update(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('driver')) {
            return Response::deny('Chỉ tài xế hoặc quản trị viên mới được chỉnh sửa chuyến đi.');
        }
        if ($trip->driver_id !== $user->id) {
            return Response::deny('Bạn không phải tài xế của chuyến đi này.');
        }
        return match ($trip->status) {
            Trip::STATUS_EDITTING, Trip::STATUS_REJECTED => Response::allow(),

            Trip::STATUS_CONFIRMED => Response::deny(
                'Chuyến đã được xác nhận. Vui lòng gửi yêu cầu mở khoá để được chỉnh sửa.'
            ),

            Trip::STATUS_PENDING => Response::deny(
                'Chuyến đang chờ cố vấn duyệt, không thể chỉnh sửa lúc này.'
            ),

            default => Response::deny('Không thể chỉnh sửa chuyến ở trạng thái hiện tại.'),
        };
    }

    /* ---------- Driver xoá: chỉ khi pending ---------- */
    public function delete(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('driver')) {
            return Response::deny('Chỉ tài xế hoặc quản trị viên mới được xoá chuyến.');
        }

        if ($trip->driver_id !== $user->id) {
            return Response::deny('Bạn không phải tài xế của chuyến này.');
        }

        if ($trip->status === Trip::STATUS_CONFIRMED) {
            return Response::deny('Chuyến đã được xác nhận, không thể xoá.');
        }

        return $trip->isDriverDeletable()
            ? Response::allow()
            : Response::deny('Chỉ có thể xoá khi chuyến ở trạng thái "pending".');
    }

    /* ---------- Driver GỬI yêu cầu mở khoá ---------- */
    public function requestReopen(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('driver') || $trip->driver_id !== $user->id) {
            return Response::deny('Chỉ tài xế của chuyến mới được gửi yêu cầu mở khoá.');
        }

        if (! $trip->canRequestReopen()) {
            return Response::deny('Chỉ gửi yêu cầu mở khoá cho chuyến đã được xác nhận.');
        }

        if ($trip->pendingReopenRequest) {
            return Response::deny('Đã có một yêu cầu mở khoá đang chờ duyệt cho chuyến này.');
        }

        return Response::allow();
    }

    /* ---------- Advisor/Admin DUYỆT yêu cầu mở khoá ---------- */
    public function reviewReopen(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('advisor')) {
            return Response::deny('Chỉ cố vấn phụ trách hoặc quản trị viên mới được duyệt yêu cầu.');
        }

        return $trip->advisor_id === $user->id
            ? Response::allow()
            : Response::deny('Bạn không phải cố vấn phụ trách chuyến này.');
    }

    /* ---------- Advisor confirm / reject chuyến ---------- */
    public function review(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('advisor')) {
            return Response::deny('Chỉ cố vấn phụ trách hoặc quản trị viên mới được duyệt chuyến.');
        }

        if ($trip->advisor_id !== $user->id) {
            return Response::deny('Bạn không phải cố vấn phụ trách chuyến này.');
        }

        return $trip->isReviewable()
            ? Response::allow()
            : Response::deny('Chuyến này không ở trạng thái chờ duyệt.');
    }

}
