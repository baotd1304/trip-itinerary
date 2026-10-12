<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import TripTable from '@/components/trips/TripTable.vue';
import TripPagination from '@/components/trips/TripPagination.vue';
import TripFormDialog from '@/components/trips/TripFormDialog.vue';
import TripDeleteDialog from '@/components/trips/TripDeleteDialog.vue';
import TripReopenRequestDialog from '@/components/trips/TripReopenRequestDialog.vue';
import TripReopenReviewDialog from '@/components/trips/TripReopenReviewDialog.vue';
import TripRejectDialog from '@/components/trips/TripRejectDialog.vue';
import TripBulkRejectDialog from '@/components/trips/TripBulkRejectDialog.vue';
import ImageLightbox from '@/components/trips/ImageLightbox.vue';
import TripFilters from '@/components/trips/TripFilters.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import trips from '@/routes/client/trips';
import { toArray } from '@/lib/array';
import { useTripDialogs } from '@/composables/useTripDialogs';
import type { Advisor, Car, Driver, PaginationLink, Trip, TripStatus } from '@/types/trip';
import { usePagination, type PaginationMeta } from '@/composables/usePagination';
import { provideTripRoutes } from '@/composables/useTripRoutes';
import { clientTripRoutes } from '@/lib/trip-routes-client';
import { DEFAULT_TRIP_TABLE_COLUMNS } from '@/types/trip-table';
import { canReview } from '@/lib/trip-permissions';
import {
    ADMIN_TABLE_ACTIONS,
    ADMIN_DIALOG_ACTIONS,
    TABLE_ONLY_VIEW,
    DIALOG_ACTIONS
} from '@/lib/trip-action-presets';

const { t } = useI18n();

defineOptions({
    layout: undefined, // giữ layout hiện tại
});

const props = defineProps<{
    trips: { data?: Trip[]; links?: unknown[] } | Trip[];
    tripsMeta?: PaginationMeta;
    cars: { data?: Car[] } | Car[];
    advisors: { data?: Advisor[] } | Advisor[];
    drivers: { data?: Driver[] } | Driver[];
    stats?: {
        pending?: number;
        editing?: number;
        pendingReopen?: number;
        rejected?: number;
    };
    filters?: {
        search?: string | null;
        status?: TripStatus | null;
        pending_reopen?: '1' | null;
        from?: string | null;
        to?: string | null;
        is_overnight?: '1' | '0' | null;
        is_holiday?: '1' | '0' | null;
    };
    can?: { create?: boolean };
}>();

// Route cho cac dialog con phia client trip
provideTripRoutes(clientTripRoutes);

/* Dữ liệu */
const items = computed(() => toArray<Trip>(props.trips));
const cars = computed(() => toArray<Car>(props.cars));
const advisors = computed(() => toArray<Advisor>(props.advisors));
const drivers = computed(() => toArray<Driver>(props.drivers));
const links = computed<PaginationLink[]>(() =>
    (props.trips as any)?.links ?? (props.trips as any)?.meta?.links ?? []);

/* ---------- Pagination dùng chung ---------- */
const { total: totalCount, startIndex, rangeFrom, rangeTo } = usePagination(
    () => props.trips,
    () => props.tripsMeta,
    () => items.value.length,
);
/* Auth / role */
const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user ?? null);
const isDriver = computed(() => ((authUser.value?.roles ?? []) as string[]).includes('driver'));
const isAdvisor = computed(() => ((authUser.value?.roles ?? []) as string[]).includes('advisor'));

/* Dialog orchestration */
const {
    mainOpen, mode, activeTrip,
    deleteOpen, reopenRequestOpen, reopenReviewOpen, rejectTripOpen,
    target, reviewAction, confirmingId, previewImage,
    openCreate, handleAction,
} = useTripDialogs(clientTripRoutes);

const bulkReviewProcessing = ref(false);
const bulkRejectOpen = ref(false);
const bulkRejectTripIds = ref<number[]>([]);
const tripFilters = ref<InstanceType<typeof TripFilters> | null>(null);
const hasStats = computed(
    () =>
        !!props.stats &&
        Object.values(props.stats).some((value) => Number(value) > 0),
);
const hasReviewableTrips = computed(() =>
    items.value.some((trip) => trip.status === 'pending' && canReview(trip)),
);

const confirmSelectedTrips = (tripIds: number[]) => {
    if (!tripIds.length || bulkReviewProcessing.value) {
        return;
    }

    bulkReviewProcessing.value = true;
    router.post(
        clientTripRoutes.bulkReview(),
        { action: 'confirm', trip_ids: tripIds },
        {
            preserveScroll: true,
            onFinish: () => {
                bulkReviewProcessing.value = false;
            },
        },
    );
};

const rejectSelectedTrips = (tripIds: number[]) => {
    bulkRejectTripIds.value = tripIds;
    bulkRejectOpen.value = true;
};

</script>

<template>
    <Head :title="t('trip.pageTitle')" />

    <Card class="overflow-hidden">
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <CardTitle>{{ $t('trip.listTitle') }}</CardTitle>

                    <div v-if="hasStats" class="flex flex-wrap items-center gap-1.5">
                        <Badge
                            as="button"
                            type="button"
                            v-if="props.stats?.pending"
                            :aria-pressed="props.filters?.status === 'pending' && !props.filters?.pending_reopen"
                            class="cursor-pointer bg-amber-500 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="tripFilters?.applyStatsFilter('pending')"
                        >
                            {{ $t('trip.status.pending') }}: {{ props.stats.pending }}
                        </Badge>
                        <Badge
                            as="button"
                            type="button"
                            v-if="props.stats?.editing"
                            :aria-pressed="props.filters?.status === 'editing' && !props.filters?.pending_reopen"
                            class="cursor-pointer bg-indigo-600 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="tripFilters?.applyStatsFilter('editing')"
                        >
                            {{ $t('trip.status.editing') }}: {{ props.stats.editing }}
                        </Badge>
                        <Badge
                            as="button"
                            type="button"
                            v-if="props.stats?.pendingReopen"
                            variant="outline"
                            :aria-pressed="props.filters?.pending_reopen === '1'"
                            class="cursor-pointer border-amber-500 text-amber-700 focus-visible:outline-2 focus-visible:outline-offset-2 dark:text-amber-400"
                            @click="tripFilters?.applyStatsFilter('pendingReopen')"
                        >
                            {{ $t('trip.admin.pendingReopen') }}:
                            {{ props.stats.pendingReopen }}
                        </Badge>
                        <Badge
                            as="button"
                            type="button"
                            v-if="props.stats?.rejected"
                            variant="destructive"
                            :aria-pressed="props.filters?.status === 'rejected' && !props.filters?.pending_reopen"
                            class="cursor-pointer border-red-500 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="tripFilters?.applyStatsFilter('rejected')"
                        >
                            {{ $t('trip.status.rejected') }}:
                            {{ props.stats.rejected }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button v-if="props.can?.create" @click="openCreate" variant="info" size="sm">
                        {{ $t('trip.create') }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Thanh tìm kiếm / lọc -->
            <TripFilters ref="tripFilters" :url="trips.index().url" :initial="props.filters" :only="['trips', 'filters', 'tripsMeta', 'stats']"/>
            <!-- Tổng số TRÊN TẤT CẢ CÁC TRANG sau khi lọc -->
            <p class="mb-2 text-xs text-muted-foreground">
                <template v-if="totalCount">
                    {{ $t('trip.filters.resultRange', {
                        from: rangeFrom,
                        to: rangeTo,
                        total: totalCount,
                    }) }}
                </template>
                <template v-else>
                    {{ $t('trip.filters.resultEmpty') }}
                </template>
            </p>
            <TripTable 
                :trips="items" 
                :start-index="startIndex" 
                :confirming-id="confirmingId" 
                storage-key="client-trip-columns" 
                :default-columns="DEFAULT_TRIP_TABLE_COLUMNS"
                :table-actions="TABLE_ONLY_VIEW"
                :bulk-review="hasReviewableTrips"
                :bulk-processing="bulkReviewProcessing"
                @action="handleAction"
                @bulk-confirm="confirmSelectedTrips"
                @bulk-reject="rejectSelectedTrips"
            />
            <TripPagination :links="links" :fallback-url="trips.index().url" />
        </CardContent>
    </Card>

    <!-- Dialog chính: Create / Edit / View -->
    <TripFormDialog
        v-model:open="mainOpen"
        :mode="mode"
        :trip="activeTrip"
        :cars="cars"
        :advisors="advisors"
        :drivers="drivers"
        :is-driver="isDriver"
        :is-advisor="isAdvisor"
        :auth-user="authUser"
        :confirming-id="confirmingId"
        @action="handleAction"
        @preview="(url) => (previewImage = url)"
        @switch-mode="(m) => (mode = m)"
    />

    <!-- Dialog phụ -->
    <TripDeleteDialog v-model:open="deleteOpen" :trip="target" />
    <TripReopenRequestDialog v-model:open="reopenRequestOpen" :trip="target" />
    <TripReopenReviewDialog v-model:open="reopenReviewOpen" :trip="target" :action="reviewAction" />
    <TripRejectDialog v-model:open="rejectTripOpen" :trip="target" />
    <TripBulkRejectDialog
        v-model:open="bulkRejectOpen"
        :trip-ids="bulkRejectTripIds"
        :action="clientTripRoutes.bulkReview()"
    />

    <ImageLightbox v-model="previewImage" />
</template>