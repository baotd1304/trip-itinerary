export type ExpenseColumnKey =
    | 'select'
    | 'id'
    | 'name'
    | 'overkm_rate'
    | 'overtime_rate'
    | 'overnight_rate'
    | 'holiday_rate'
    | 'note'
    | 'is_active'
    | 'created_by'
    | 'created_at'
    | 'updated_at'
    | 'actions';

export interface ExpenseColumn {
    key: ExpenseColumnKey;
    labelKey: string;
    required?: boolean;
}

export const EXPENSE_TABLE_COLUMNS: ExpenseColumn[] = [
    { key: 'select', labelKey: 'common.select', required: true },
    { key: 'id', labelKey: 'expense.fields.id', required: true },
    { key: 'name', labelKey: 'expense.fields.name', required: true },
    { key: 'overkm_rate', labelKey: 'expense.fields.overkmRate' },
    { key: 'overtime_rate', labelKey: 'expense.fields.overtimeRate' },
    { key: 'overnight_rate', labelKey: 'expense.fields.overnightRate' },
    { key: 'holiday_rate', labelKey: 'expense.fields.holidayRate' },
    { key: 'is_active', labelKey: 'expense.fields.isActive' },
    { key: 'created_by', labelKey: 'expense.fields.createdBy' },
    { key: 'created_at', labelKey: 'expense.fields.createdAt' },
    { key: 'updated_at', labelKey: 'expense.fields.updatedAt' },
    { key: 'note', labelKey: 'expense.fields.note' },
    { key: 'actions', labelKey: 'common.actions', required: true },
];

export const DEFAULT_EXPENSE_TABLE_COLUMNS: ExpenseColumnKey[] =
    EXPENSE_TABLE_COLUMNS.map((column) => column.key);

export const ADMIN_EXPENSE_TABLE_ACTIONS = [
    'view',
    'edit',
    'toggleActive',
    'delete',
] as const;
