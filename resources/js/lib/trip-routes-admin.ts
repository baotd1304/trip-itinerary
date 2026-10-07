import trips from '@/routes/admin/trips';
import type { TripRouteContext } from '@/composables/useTripRoutes';

export const adminTripRoutes: TripRouteContext = {
    index: () => trips.index(),
    store: trips.store,
    update: trips.update,
    destroy: trips.destroy,
    confirm: (id) => `/admin/trips/${id}/confirm`,
    reject: (id) => `/admin/trips/${id}/reject`,
    reopenRequest: (id) => `/admin/trips/${id}/reopen-requests`,
    reopenReview: (rid, action) => `/admin/reopen-requests/${rid}/${action}`,
    cloudinarySignature: '/admin/trips/cloudinary-signature',
    discardImage:        '/admin/trips/uploaded-image',
};
