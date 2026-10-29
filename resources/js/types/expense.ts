export interface ExpenseCreator {
    id: number;
    name: string;
}

export interface ExpensePermissions {
    update?: boolean;
    delete?: boolean;
    toggleActive?: boolean;
}

export interface AppExpense {
    id: number;
    name: string;
    overkm_rate: number | null;
    overtime_rate: number | null;
    overnight_rate: number | null;
    holiday_rate: number | null;
    note: string | null;
    is_active: boolean | 0 | 1;
    created_by: number | null;
    creator?: ExpenseCreator | null;
    trip_expenses_count?: number;
    created_at: string | null;
    updated_at: string | null;
    can?: ExpensePermissions;
}

export type ExpenseActionType = 'view' | 'edit' | 'delete' | 'toggleActive';
export type ExpenseDialogMode = 'create' | 'edit' | 'view';
export type ExpenseBulkAction = 'activate' | 'deactivate' | 'delete';
