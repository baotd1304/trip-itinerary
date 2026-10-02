import type { Trip, TripStatus } from '@/types/trip';

/** Trạng thái còn có thể duyệt/từ chối */
const REVIEWABLE: TripStatus[] = ['pending', 'editing'];

/** Mặc định FALSE — không có cờ từ server nghĩa là KHÔNG có quyền */
export const canUpdate = (t?: Trip | null): boolean => t?.can?.update === true;

export const canDelete = (t?: Trip | null): boolean => t?.can?.delete === true;

export const canRequestReopen = (t?: Trip | null): boolean =>
  t?.can?.requestReopen === true && t?.status === 'confirmed';

/** Xác nhận / Từ chối: cần cả QUYỀN và ĐÚNG TRẠNG THÁI */
export const canReview = (t?: Trip | null): boolean =>
  t?.can?.review === true
  && !!t?.status
  && REVIEWABLE.includes(t.status)
  && !t?.pending_reopen_request; // đang chờ xử lý mở khoá thì không duyệt chuyến

/** Duyệt/từ chối yêu cầu mở khoá: phải có request đang chờ */
export const canReviewReopen = (t?: Trip | null): boolean =>
  t?.can?.reviewReopen === true && !!t?.pending_reopen_request;

export const hasAnyAction = (t?: Trip | null) =>
    canUpdate(t) || canDelete(t) || canRequestReopen(t) || canReview(t) || canReviewReopen(t);

/** Key i18n cho tooltip nút Sửa */
export const updateHintKey = (t?: Trip | null): string => {
    if (!t) return 'trip.hints.updateNoPermission';
    if (!canUpdate(t)) {
        return t.status === 'confirmed'
            ? 'trip.hints.updateConfirmed'
            : 'trip.hints.updateNoPermission';
    }
    switch (t.status) {
        case 'pending':  return 'trip.hints.updatePending';
        case 'editing':  return 'trip.hints.updateEditing';
        case 'rejected': return 'trip.hints.updateRejected';
        default:         return 'trip.hints.updatePending';
    }
};

export const deleteHintKey = (t?: Trip | null) =>
    canDelete(t) ? 'trip.hints.deleteAllowed' : 'trip.hints.deleteDenied';

/** Nút Sửa hiển thị "Sửa lại" khi trip bị từ chối */
export const editLabelKey = (t?: Trip | null) =>
    t?.status === 'rejected' ? 'trip.actions.editRejected' : 'trip.actions.edit';