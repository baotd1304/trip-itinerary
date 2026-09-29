import type { TripActionType } from '@/types/trip';

/** Bảng danh sách: chỉ mở dialog chi tiết */
export const TABLE_ONLY_VIEW: TripActionType[] = ['view'];

/** Dialog chi tiết: đầy đủ nút trừ 'view' */
export const DIALOG_ACTIONS: TripActionType[] = [
    'edit', 'delete', 'request-reopen',
    'approve-reopen', 'reject-reopen',
    'confirm', 'reject',
];

/** Admin muốn duyệt nhanh ngay trên bảng (tuỳ chọn) */
export const TABLE_QUICK_REVIEW: TripActionType[] = ['view', 'confirm', 'reject'];