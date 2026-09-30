import { toDateInput, toTimeInput } from '@/composables/useFormat';
import type { Trip, TripFormModel } from '@/types/trip';

const toIdOrEmpty = (v?: number | null): number | '' =>
    typeof v === 'number' && !Number.isNaN(v) ? v : '';

export const emptyTripForm = (): TripFormModel => ({
    id: 0,
    advisor_id: '', driver_id: '', car_id: '',
    day: '', origin: '', destination: '',
    departure_time: '', arrival_time: '',
    odo_start: 0, odo_end: 0, overtime: 0,
    toll_fee: 0, airport_fee: 0,
    is_overnight: false, is_holiday: false,
    note: '',
    status: 'pending',
    distance: 0,
    total_fee: 0,
    advisor: null,
    driver: null,
    reject_reason: null,
    reviewer: null,
    pending_reopen_request: null,
    latest_reopen_request: null,
});

export const mapTripToForm = (trip: Trip): TripFormModel => ({
    ...emptyTripForm(),
    ...trip,
    advisor_id: toIdOrEmpty(trip.advisor?.id),
    driver_id: toIdOrEmpty(trip.driver?.id),
    car_id: Number(trip.car_id),
    day: toDateInput(trip.day),
    departure_time: toTimeInput(trip.departure_time),
    arrival_time: toTimeInput(trip.arrival_time),
    overtime: Number(trip.trip_expense?.overtime ?? 0),
    toll_fee: Number(trip.trip_expense?.toll_fee ?? 0),
    airport_fee: Number(trip.trip_expense?.airport_fee ?? 0),
    is_overnight: Boolean(trip.trip_expense?.is_overnight),
    is_holiday: Boolean(trip.trip_expense?.is_holiday),
    note: trip.note ?? '',
    reject_reason: trip.reject_reason ?? null,
    reviewer: trip.reviewer ?? null,
    pending_reopen_request: trip.pending_reopen_request ?? null,
    latest_reopen_request: trip.latest_reopen_request ?? null,
});