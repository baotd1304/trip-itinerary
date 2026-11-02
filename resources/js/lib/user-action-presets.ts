import type { UserActionType } from '@/types/user';

/** Bảng danh sách phía admin */
export const ADMIN_USER_TABLE_ACTIONS: UserActionType[] = [
    'view', 'assignRole', 'toggleActive', 'verifyEmail', 'delete',
];

/** Trong dialog xem chi tiết */
export const USER_DIALOG_ACTIONS: UserActionType[] = [
    'edit', 'assignRole', 'toggleActive', 'verifyEmail', 'delete',
];