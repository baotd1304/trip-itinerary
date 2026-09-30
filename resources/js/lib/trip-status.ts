import type { ReopenRequest, Trip, TripStatus } from '@/types/trip';

export const STATUS_CLASS: Record<TripStatus, string> = {
    pending: 'bg-amber-500',
    editing: 'bg-indigo-600',
    confirmed: 'bg-emerald-600',
    rejected: 'bg-red-600',
};

export const statusClass = (s?: TripStatus) =>
    (s && STATUS_CLASS[s]) || 'bg-gray-500';

/** Key i18n của trạng thái: trip.status.pending ... */
export const statusKey = (s?: TripStatus) => `trip.status.${s ?? 'pending'}`;

/* ---------- Quan hệ yêu cầu mở khoá ---------- */
export const latestReopen = (t?: Trip | null): ReopenRequest | null =>
    t?.latest_reopen_request ?? null;

export const approvedReopen = (t?: Trip | null) => {
    const r = latestReopen(t);
    return r?.status === 'approved' ? r : null;
};

export const rejectedReopen = (t?: Trip | null) => {
    const r = latestReopen(t);
    return r?.status === 'rejected' ? r : null;
};

/** Key cảnh báo khi driver submit form edit */
export const submitWarningKey = (s: TripStatus): string | null => {
    switch (s) {
        case 'pending':  return 'trip.warnings.pending';
        case 'editing':  return 'trip.warnings.editing';
        case 'rejected': return 'trip.warnings.rejected';
        default:         return null;
    }
};