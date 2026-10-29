export interface CarPermissions {
    update?: boolean;
    delete?: boolean;
    toggleActive?: boolean;
}

export interface AppCar {
    id: number;
    name: string | null;
    brand: string | null;
    model: string | null;
    year: number | null;
    license_plate: string;
    owner: string | null;
    is_active: boolean | 0 | 1;
    created_at: string | null;
    updated_at: string | null;
    can?: CarPermissions;
}

export type CarActionType = 'view' | 'edit' | 'delete' | 'toggleActive';
export type CarDialogMode = 'create' | 'edit' | 'view';
export type CarBulkAction = 'activate' | 'deactivate' | 'delete';
