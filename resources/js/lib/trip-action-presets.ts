import type { TripActionType } from '@/types/trip';

/** Bảng danh sách: chỉ mở dialog chi tiết */
export const TABLE_ONLY_VIEW: TripActionType[] = ['view'];

/** Dialog chi tiết: đầy đủ nút trừ 'view' */
export const DIALOG_ACTIONS: TripActionType[] = [
    'edit', 'delete', 'request-reopen',
    'approve-reopen', 'reject-reopen',
    'confirm', 'reject',
];

/** Admin ở bảng: xem + duyệt nhanh */
export const ADMIN_TABLE_ACTIONS: TripActionType[] = ['view', 'confirm', 'reject'];

/** Admin trong dialog: toàn quyền */
export const ADMIN_DIALOG_ACTIONS: TripActionType[] = [
    'edit',
    'delete',
    'approve-reopen',
    'reject-reopen',
    'confirm',
    'reject',
];