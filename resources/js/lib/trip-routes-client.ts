import trips from '@/routes/client/trips';
import type { TripRouteContext } from '@/composables/useTripRoutes';

export const clientTripRoutes: TripRouteContext = {
    index: () => trips.index(),
    store: trips.store,
    update: trips.update,
    destroy: trips.destroy,
    confirm: (id) => `/trips/${id}/confirm`,
    reject: (id) => `/trips/${id}/reject`,
    bulkReview: () => '/trips/bulk-review',
    reopenRequest: (id) => `/trips/${id}/reopen-requests`,
    reopenReview: (rid, action) => `/reopen-requests/${rid}/${action}`,
    cloudinarySignature: '/trips/cloudinary-signature',
    discardImage:        '/trips/uploaded-image',
};