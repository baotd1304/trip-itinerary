export type CarColumnKey =
    | 'select'
    | 'id'
    | 'name'
    | 'brand'
    | 'model'
    | 'year'
    | 'license_plate'
    | 'owner'
    | 'is_active'
    | 'created_at'
    | 'updated_at'
    | 'actions';

export interface CarColumn {
    key: CarColumnKey;
    labelKey: string;
    required?: boolean;
}

export const CAR_TABLE_COLUMNS: CarColumn[] = [
    { key: 'select', labelKey: 'common.select', required: true },
    { key: 'id', labelKey: 'car.table.id', required: true },
    { key: 'name', labelKey: 'car.fields.name', required: true },
    { key: 'brand', labelKey: 'car.fields.brand' },
    { key: 'model', labelKey: 'car.fields.model' },
    { key: 'year', labelKey: 'car.fields.year' },
    { key: 'license_plate', labelKey: 'car.fields.licensePlate' },
    { key: 'owner', labelKey: 'car.fields.owner' },
    { key: 'is_active', labelKey: 'car.fields.isActive' },
    { key: 'created_at', labelKey: 'car.fields.createdAt' },
    { key: 'updated_at', labelKey: 'car.fields.updatedAt' },
    { key: 'actions', labelKey: 'common.actions', required: true },
];

export const DEFAULT_CAR_TABLE_COLUMNS: CarColumnKey[] = [
    'select',
    'id',
    'name',
    'brand',
    'model',
    'year',
    'license_plate',
    'owner',
    'is_active',
    'created_at',
    'updated_at',
    'actions',
];
