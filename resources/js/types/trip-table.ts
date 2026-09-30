export type ColumnKey =
    | 'stt'
    | 'advisor'
    | 'driver'
    | 'origin'
    | 'destination'
    | 'day'
    | 'time'
    | 'distance'
    | 'status'
    | 'overnight'
    | 'holiday'
    | 'totalFee'
    | 'actions';

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
        key: 'advisor',
        labelKey: 'trip.table.advisor',
    },
    {
        key: 'driver',
        labelKey: 'trip.table.driver',
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
        key: 'day',
        labelKey: 'trip.table.day',
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
        key: 'actions',
        labelKey: 'trip.table.actions',
        required: true,
    },
];

export const DEFAULT_TRIP_TABLE_COLUMNS: ColumnKey[] = [
    'stt',
    'advisor',
    'driver',
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