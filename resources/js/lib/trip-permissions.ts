import type { Trip } from '@/types/trip';

export const canUpdate = (t?: Trip | null) => t?.can?.update === true;
export const canDelete = (t?: Trip | null) => t?.can?.delete === true;
export const canRequestReopen = (t?: Trip | null) => t?.can?.requestReopen === true;
export const canReview = (t?: Trip | null) => t?.can?.review === true;
export const canReviewReopen = (t?: Trip | null) =>
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