export type TripStatus = 'pending' | 'editing' | 'confirmed' | 'rejected';
export type DialogMode = 'create' | 'edit' | 'view';

export type TripActionType =
    | 'view' | 'edit' | 'delete'
    | 'request-reopen' | 'approve-reopen' | 'reject-reopen'
    | 'confirm' | 'reject';

export interface PersonRef { id: number; name: string }
export interface Car { id: number; license_plate: string; is_active: boolean }
export interface Advisor { id: number; name: string }
export interface Driver { id: number; name: string }
export interface TripImage { id: number; url: string; public_id: string }
export interface PaginationLink { url: string | null; label: string; active: boolean }

export interface TripExpense {
    id: number; trip_id: number; overtime: number;
    toll_fee: number; airport_fee: number;
    is_overnight: boolean; is_holiday: boolean;
}

export interface ReopenRequest {
    id: number;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    review_note: string | null;
    reviewed_at?: string | null;
    requester?: PersonRef | null;
    reviewer?: PersonRef | null;
    created_at: string;
}

export interface TripAbilities {
    update: boolean; delete: boolean;
    requestReopen: boolean; review: boolean; reviewReopen: boolean;
}

export interface Trip {
    [x: string]: any;
    id: number;
    advisor?: PersonRef | null;
    driver?: PersonRef | null;
    car_id: number;
    day: string;
    origin: string;
    destination: string;
    departure_time: string;
    arrival_time: string;
    odo_start: number;
    odo_end: number;
    distance: number;
    total_fee: number;
    note: string | null;
    status: TripStatus;
    reject_reason?: string | null;
    reviewer?: PersonRef | null;
    trip_expense?: TripExpense | null;
    images?: TripImage[];
    can?: TripAbilities;
    pending_reopen_request?: ReopenRequest | null;
    latest_reopen_request?: ReopenRequest | null;
}

export interface TripFilters {
    search?: string | null;
    status?: TripStatus | null;
    from?: string | null;
    to?: string | null;
    is_overnight?: '1' | '0' | null;
    is_holiday?: '1' | '0' | null;
}

/** Payload phát ra từ TripActions */
export interface TripActionPayload {
    type: TripActionType;
    trip: Trip;
}

/** Model dùng cho form create/edit */
export interface TripFormModel {
    id: number;
    advisor_id: number | '';
    driver_id: number | '';
    car_id: number | '';
    day: string;
    origin: string;
    destination: string;
    departure_time: string;
    arrival_time: string;
    odo_start: number;
    odo_end: number;
    overtime: number;
    toll_fee: number;
    airport_fee: number;
    is_overnight: boolean;
    is_holiday: boolean;
    note: string;
    status: TripStatus;
    distance: number;
    total_fee: number;
    advisor: PersonRef | null;
    driver: PersonRef | null;
    reject_reason: string | null;
    reviewer: PersonRef | null;
    pending_reopen_request: ReopenRequest | null;
    latest_reopen_request: ReopenRequest | null;
}