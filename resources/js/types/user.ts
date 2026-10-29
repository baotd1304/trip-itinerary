export type RoleName = 'admin' | 'manager' | 'editor' | 'advisor' | 'driver' | string;

export interface RoleRef {
    id: number;
    name: RoleName;
    permissions?: PermissionRef[];
}

export interface PermissionRef {
    id: number;
    name: string;
}

export interface UserRef {
    id: number;
    name: string;
}

export interface UserPermissions {
    update?: boolean;
    delete?: boolean;
    assignRole?: boolean;
    toggleActive?: boolean;
    verifyEmail?: boolean;
    resetPassword?: boolean;
}

export interface AppUser {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean | 0 | 1;
    email_verified_at: string | null;
    created_by: number | null;
    creator?: UserRef | null;
    roles?: RoleRef[];
    direct_permissions?: string[];
    created_at: string | null;
    updated_at: string | null;
    can?: UserPermissions;
}
export interface PaginationLink { url: string | null; label: string; active: boolean }


export type UserActionType =
    | 'view'
    | 'edit'
    | 'delete'
    | 'toggleActive'
    | 'verifyEmail'
    | 'assignRole';

export type UserDialogMode = 'create' | 'edit' | 'view';