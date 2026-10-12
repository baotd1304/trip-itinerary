import type { TripRouteContext } from '@/composables/useTripRoutes';
import trips from '@/routes/admin/trips';

export const adminTripRoutes = {
    index: () => trips.index(),
    store: trips.store,
    update: trips.update,
    destroy: trips.destroy,
    confirm: (id) => `/admin/trips/${id}/confirm`,
    reject: (id) => `/admin/trips/${id}/reject`,
    bulkReview: () => '/admin/trips/bulk-review',
    reopenRequest: (id) => `/admin/trips/${id}/reopen-requests`,
    reopenReview: (rid, action) => `/admin/reopen-requests/${rid}/${action}`,
    cloudinarySignature: '/admin/trips/cloudinary-signature',
    discardImage:        '/admin/trips/uploaded-image',
} satisfies TripRouteContext & { bulkReview: () => string };
