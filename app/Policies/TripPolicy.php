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
        return $this->isParticipant($user, $trip);      //chi các user là driver hoặc advisor của chuyến đi mới được xem
    }

    // create: driver, editor được phép tạo chuyến đi
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['driver', 'editor']);
    }

    /**
     * Determine whether the user can update the model.
     * driver: chỉ edit được chuyến đi của mình khi status = 'pending', 'editting'
     * admin, editor: edit được trừ khi status = 'confirmed'
     */
    public function update(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('driver')) {
            return Response::deny('Chỉ tài xế hoặc quản trị viên mới được chỉnh sửa chuyến đi.');
        }
        if ($trip->driver_id !== $user->id) {
            return Response::deny('Bạn không phải tài xế của chuyến đi này.');
        }
        if ($trip->isLocked()) {
            return Response::deny('Chuyến đi đã được xác nhận (confirmed), không thể chỉnh sửa.');
        }
        return in_array($trip->status, Trip::EDITABLE_STATUSES, true)
            ? Response::allow()
            : Response::deny('Chỉ có thể chỉnh sửa khi chuyến đi ở trạng thái "pending, editting".');
    }

    /**
     * Determine whether the user can delete the model.
     * driver: chỉ delete được chuyến đi của mình khi status = 'pending'
     * admin, editor: chi xoa duoc khi status = 'pending'
     */
    public function delete(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('driver')) {
            return Response::deny('Chỉ tài xế hoặc quản trị viên mới được xoá chuyến đi.');
        }

        if ($trip->driver_id !== $user->id) {
            return Response::deny('Bạn không phải tài xế của chuyến đi này.');
        }

        if ($trip->isLocked()) {
            return Response::deny('Chuyến đi đã được xác nhận (confirmed), không thể xoá.');
        }

        return in_array($trip->status, Trip::DELETABLE_STATUSES, true)
            ? Response::allow()
            : Response::deny('Chỉ có thể xoá khi chuyến đi ở trạng thái "pending".');
    }

    public function restore(User $user, Trip $trip): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Trip $trip): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Mở khoá chuyến đi để driver sửa lại (confirmed|rejected -> editting).
     * Admin: luôn được (qua before()).
     * Advisor: chỉ với chuyến đi mình phụ trách.
     * Driver: KHÔNG được tự mở khoá.
     */
    public function reopen(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('advisor')) {
            return Response::deny('Chỉ cố vấn phụ trách hoặc quản trị viên mới được mở khoá chỉnh sửa.');
        }

        if ($trip->advisor_id !== $user->id) {
            return Response::deny('Bạn không phải cố vấn phụ trách chuyến đi này.');
        }

        if ($trip->status === Trip::STATUS_EDITTING) {
            return Response::deny('Chuyến đi đang ở trạng thái "editting", tài xế đã có thể chỉnh sửa.');
        }

        return $trip->isReopenable()
            ? Response::allow()
            : Response::deny('Chỉ mở khoá được chuyến đi đã "confirmed" hoặc "rejected".');
    }

    /**
     * (Tuỳ chọn) Advisor xác nhận chuyến đi: editting|pending -> confirmed.
     */
    public function confirm(User $user, Trip $trip): Response
    {
        if (! $user->hasRole('advisor') || $trip->advisor_id !== $user->id) {
            return Response::deny('Bạn không có quyền xác nhận chuyến đi này.');
        }

        return in_array($trip->status, [Trip::STATUS_PENDING, Trip::STATUS_EDITTING], true)
            ? Response::allow()
            : Response::deny('Chuyến đi này không ở trạng thái chờ xác nhận.');
    }

    private function isParticipant(User $user, Trip $trip): bool
    {
        return $trip->driver_id === $user->id || $trip->advisor_id === $user->id;
    }


}
