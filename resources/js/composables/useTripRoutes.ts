import { inject, provide, type InjectionKey } from 'vue';

export interface TripRouteContext {
    /** index().url dùng cho filter + pagination */
    index: () => { url: string };
    /** Form props cho Inertia <Form v-bind="..."> */
    store: { form: () => Record<string, unknown> };
    update: { form: (id: number) => Record<string, unknown> };
    destroy: { form: (id: number) => Record<string, unknown> };
    /** Các action dạng URL thuần */
    confirm: (id: number) => string;
    reject: (id: number) => string;
    bulkReview: () => string;
    reopenRequest: (id: number) => string;
    reopenReview: (requestId: number, action: 'approve' | 'reject') => string;
    cloudinarySignature: string;
    discardImage: string;
}

const TRIP_ROUTES: InjectionKey<TripRouteContext> = Symbol('trip-routes');

export function provideTripRoutes(ctx: TripRouteContext) {
    provide(TRIP_ROUTES, ctx);
}

export function useTripRoutes(): TripRouteContext {
    const ctx = inject(TRIP_ROUTES, null);
    if (!ctx) {
        throw new Error(
            'useTripRoutes(): thiếu provideTripRoutes() ở page cha (ListTrip.vue / admin Trip.vue).',
        );
    }
    return ctx;
}