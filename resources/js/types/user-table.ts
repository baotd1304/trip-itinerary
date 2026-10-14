export type UserColumnKey =
    | 'select'
    | 'stt'
    | 'name'
    | 'email'
    | 'phone'
    | 'roles'
    | 'is_active'
    | 'email_verified_at'
    | 'creator'
    | 'created_at'
    | 'updated_at'
    | 'actions';

export interface UserColumn {
    key: UserColumnKey;
    labelKey: string;
    required?: boolean;
    align?: 'left' | 'center' | 'right';
}

export const USER_TABLE_COLUMNS: UserColumn[] = [
    { key: 'select',            labelKey: 'common.select',          required: true, align: 'center' },
    { key: 'stt',               labelKey: 'common.number',           required: true, align: 'center' },
    { key: 'name',              labelKey: 'user.fields.name',       required: true, align: 'left' },
    { key: 'email',             labelKey: 'user.fields.email',      align: 'left' },
    { key: 'phone',             labelKey: 'user.fields.phone',      align: 'center' },
    { key: 'roles',             labelKey: 'user.fields.roles',      align: 'center' },
    { key: 'is_active',         labelKey: 'user.fields.isActive',   align: 'center' },
    { key: 'email_verified_at', labelKey: 'user.fields.verified',   align: 'center' },
    { key: 'creator',           labelKey: 'user.fields.createdBy',  align: 'center' },
    { key: 'created_at',        labelKey: 'user.fields.createdAt',  align: 'center' },
    { key: 'updated_at',        labelKey: 'user.fields.updatedAt',  align: 'center' },
    { key: 'actions',           labelKey: 'common.actions',         required: true, align: 'center' },
];

export const DEFAULT_USER_TABLE_COLUMNS: UserColumnKey[] = [
    'select', 'stt', 'name', 'email', 'phone', 'roles', 'is_active', 'email_verified_at', 'created_at', 'actions',
];

export const ADMIN_DEFAULT_USER_TABLE_COLUMNS: UserColumnKey[] = [
    ...DEFAULT_USER_TABLE_COLUMNS, 'creator', 'updated_at',
];