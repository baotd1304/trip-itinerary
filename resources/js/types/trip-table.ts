export type ColumnKey =
    | 'stt'
    | 'advisor'
    | 'driver'
    | 'car'
    | 'origin'
    | 'destination'
    | 'day'
    | 'time'
    | 'distance'
    | 'status'
    | 'overnight'
    | 'holiday'
    | 'totalFee'
    | 'actions'
    | 'reject_reason'
    | 'submitted_at'
    | 'reviewed_at'
    | 'reviewer'
    | 'created_at'
    | 'updated_at';


export interface TableColumn {
    key: ColumnKey;
    labelKey: string;
    required?: boolean;
}

export const TRIP_TABLE_COLUMNS: TableColumn[] = [
    {
        key: 'stt',
        labelKey: 'trip.table.stt',
    },
    {
        key: 'day',
        labelKey: 'trip.table.day',
    },
    {
        key: 'advisor',
        labelKey: 'trip.table.advisor',
    },
    {
        key: 'driver',
        labelKey: 'trip.table.driver',
    },
    {
        key: 'car',
        labelKey: 'trip.table.car',
    },
    {
        key: 'origin',
        labelKey: 'trip.table.origin',
    },
    {
        key: 'destination',
        labelKey: 'trip.table.destination',
    },
    
    {
        key: 'time',
        labelKey: 'trip.table.time',
    },
    {
        key: 'distance',
        labelKey: 'trip.table.distance',
    },
    {
        key: 'status',
        labelKey: 'trip.table.status',
    },
    {
        key: 'overnight',
        labelKey: 'trip.table.overnight',
    },
    {
        key: 'holiday',
        labelKey: 'trip.table.holiday',
    },
    {
        key: 'totalFee',
        labelKey: 'trip.table.totalFee',
    },
    {
        key: 'created_at',
        labelKey: 'trip.table.created_at',
    },
    {
        key: 'updated_at',
        labelKey: 'trip.table.updated_at',
    },
    {
        key: 'submitted_at',
        labelKey: 'trip.table.submitted_at',
    },
    {
        key: 'reviewed_at',
        labelKey: 'trip.table.reviewed_at',
    },
    {
        key: 'reviewer',
        labelKey: 'trip.table.reviewer',
    },
    {
        key: 'actions',
        labelKey: 'trip.table.actions',
        required: true,
    },
];

//client
export const DEFAULT_TRIP_TABLE_COLUMNS: ColumnKey[] = [
    'stt',
    // 'advisor',
    // 'driver',
    'car',
    'origin',
    'destination',
    'day',
    'time',
    'distance',
    'status',
    'overnight',
    'holiday',
    'totalFee',
    'actions',
];

//  preset cột cho admin
export const ADMIN_DEFAULT_TRIP_TABLE_COLUMNS: ColumnKey[] = [
    'stt',
    // 'advisor',
    // 'driver',
    'car',
    'origin',
    'destination',
    'day',
    'time',
    'distance',
    'status',
    'overnight',
    'holiday',
    'totalFee',
    'actions',
];