import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { canUpdate } from '@/lib/trip-permissions';
import type { DialogMode, Trip, TripActionPayload } from '@/types/trip';

export function useTripDialogs() {
    /* Dialog chính: create / edit / view */
    const mainOpen = ref(false);
    const mode = ref<DialogMode>('create');
    const activeTrip = ref<Trip | null>(null);

    /* Dialog phụ */
    const deleteOpen = ref(false);
    const reopenRequestOpen = ref(false);
    const reopenReviewOpen = ref(false);
    const rejectTripOpen = ref(false);
    const target = ref<Trip | null>(null);
    const reviewAction = ref<'approve' | 'reject'>('approve');

    const confirmingId = ref<number | null>(null);
    const previewImage = ref<string | null>(null);

    /** Đóng dialog chính trước để tránh xung đột focus-trap của shadcn */
    const openAfterMain = (fn: () => void) => {
        if (mainOpen.value) {
            mainOpen.value = false;
            setTimeout(fn, 180);
            return;
        }
        fn();
    };

    const openCreate = () => {
        mode.value = 'create';
        activeTrip.value = null;
        mainOpen.value = true;
    };

    const openView = (trip: Trip) => {
        mode.value = 'view';
        activeTrip.value = trip;
        mainOpen.value = true;
    };

    const openEdit = (trip?: Trip | null) => {
        if (!trip || !canUpdate(trip)) return;
        mode.value = 'edit';
        activeTrip.value = trip;
        mainOpen.value = true;
    };

    const openDelete = (trip?: Trip | null) => {
        if (!trip) return;
        openAfterMain(() => { target.value = trip; deleteOpen.value = true; });
    };

    const openReopenRequest = (trip?: Trip | null) => {
        if (!trip) return;
        openAfterMain(() => { target.value = trip; reopenRequestOpen.value = true; });
    };

    const openReopenReview = (trip: Trip | null | undefined, action: 'approve' | 'reject') => {
        if (!trip) return;
        openAfterMain(() => {
            target.value = trip;
            reviewAction.value = action;
            reopenReviewOpen.value = true;
        });
    };

    const openRejectTrip = (trip?: Trip | null) => {
        if (!trip) return;
        openAfterMain(() => { target.value = trip; rejectTripOpen.value = true; });
    };

    const confirmTrip = (trip?: Trip | null) => {
        if (!trip || confirmingId.value) return;
        confirmingId.value = trip.id;
        router.patch(`/trips/${trip.id}/confirm`, {}, {
            preserveScroll: true,
            onSuccess: () => { if (activeTrip.value?.id === trip.id) mainOpen.value = false; },
            onFinish: () => { confirmingId.value = null; },
        });
    };

    /** Bộ điều phối duy nhất cho mọi hành động phát ra từ TripActions */
    const handleAction = ({ type, trip }: TripActionPayload) => {
        switch (type) {
            case 'view':            return openView(trip);
            case 'edit':            return openEdit(trip);
            case 'delete':          return openDelete(trip);
            case 'request-reopen':  return openReopenRequest(trip);
            case 'approve-reopen':  return openReopenReview(trip, 'approve');
            case 'reject-reopen':   return openReopenReview(trip, 'reject');
            case 'confirm':         return confirmTrip(trip);
            case 'reject':          return openRejectTrip(trip);
        }
    };

    return {
        mainOpen, mode, activeTrip,
        deleteOpen, reopenRequestOpen, reopenReviewOpen, rejectTripOpen,
        target, reviewAction, confirmingId, previewImage,
        openCreate, openView, openEdit, handleAction,
    };
}