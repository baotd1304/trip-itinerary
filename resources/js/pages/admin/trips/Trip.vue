<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

import TripFilters from '@/components/trips/TripFilters.vue';
import TripTable from '@/components/trips/TripTable.vue';
import TripPagination from '@/components/trips/TripPagination.vue';
import TripFormDialog from '@/components/trips/TripFormDialog.vue';
import TripDeleteDialog from '@/components/trips/TripDeleteDialog.vue';
import TripReopenRequestDialog from '@/components/trips/TripReopenRequestDialog.vue';
import TripReopenReviewDialog from '@/components/trips/TripReopenReviewDialog.vue';
import TripRejectDialog from '@/components/trips/TripRejectDialog.vue';
import ImageLightbox from '@/components/trips/ImageLightbox.vue';

import { provideTripRoutes } from '@/composables/useTripRoutes';
import { adminTripRoutes } from '@/lib/trip-routes-admin';
import { useTripDialogs } from '@/composables/useTripDialogs';
import { usePagination } from '@/composables/usePagination';
import { toArray } from '@/lib/array';
import {
    ADMIN_TABLE_ACTIONS,
    ADMIN_DIALOG_ACTIONS,
    TABLE_ONLY_VIEW,
    DIALOG_ACTIONS
} from '@/lib/trip-action-presets';
import { ADMIN_DEFAULT_TRIP_TABLE_COLUMNS } from '@/types/trip-table';
import type {
    Advisor,
    Car,
    Driver,
    PaginationLink,
    Trip,
    TripStatus,
} from '@/types/trip';
import type { PaginationMeta } from '@/composables/usePagination';

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
    };
    filters?: {
        search?: string | null;
        status?: TripStatus | null;
        from?: string | null;
        to?: string | null;
        is_overnight?: '1' | '0' | null;
        is_holiday?: '1' | '0' | null;
    };
    can?: { create?: boolean };
}>();

/* ---------- Route context cho toàn bộ dialog con ---------- */
provideTripRoutes(adminTripRoutes);

/* ---------- Chuẩn hoá dữ liệu ---------- */
const items = computed(() => toArray<Trip>(props.trips));
const cars = computed(() => toArray<Car>(props.cars));
const advisors = computed(() => toArray<Advisor>(props.advisors));
const drivers = computed(() => toArray<Driver>(props.drivers));
const links = computed<PaginationLink[]>(() =>
    (props.trips as any)?.links ?? (props.trips as any)?.meta?.links ?? []);

/* ---------- Phân trang ---------- */
const { total: totalCount, startIndex, rangeFrom, rangeTo } = usePagination(
    computed(() => props.trips),
    computed(() => props.tripsMeta),
    () => items.value.length,
);

/* ---------- Danh tính ---------- */
const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user ?? null);

/* Admin không tự gán mình làm driver/advisor */
const isDriver = false;
const isAdvisor = false;

/* ---------- Dialog orchestration ---------- */
const {
    mode,
    mainOpen,
    activeTrip,
    target,
    reviewAction,
    deleteOpen,
    reopenRequestOpen,
    reopenReviewOpen,
    rejectTripOpen,
    previewImage,
    confirmingId,
    openCreate,
    openView,
    handleAction,
} = useTripDialogs(adminTripRoutes);

/* ---------- Thống kê nhanh trên header ---------- */
const hasStats = computed(
    () =>
        !!props.stats &&
        Object.values(props.stats).some((v) => Number(v) > 0),
);
</script>

<template>
    <Head :title="t('trip.admin.pageTitle')" />

    <Card class="overflow-hidden">
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <CardTitle>{{ $t('trip.admin.listTitle') }}</CardTitle>

                    <div v-if="hasStats" class="flex flex-wrap items-center gap-1.5">
                        <Badge
                            v-if="props.stats?.pending"
                            class="bg-amber-500 text-white"
                        >
                            {{ $t('trip.status.pending') }}: {{ props.stats.pending }}
                        </Badge>
                        <Badge
                            v-if="props.stats?.editing"
                            class="bg-indigo-600 text-white"
                        >
                            {{ $t('trip.status.editing') }}: {{ props.stats.editing }}
                        </Badge>
                        <Badge
                            v-if="props.stats?.pendingReopen"
                            variant="outline"
                            class="border-amber-500 text-amber-700 dark:text-amber-400"
                        >
                            {{ $t('trip.admin.pendingReopen') }}:
                            {{ props.stats.pendingReopen }}
                        </Badge>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button v-if="props.can?.create" @click="openCreate">
                        {{ $t('trip.create') }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <TripFilters
                :url="adminTripRoutes.index().url"
                :initial="props.filters"
                :only="['trips', 'tripsMeta', 'stats', 'filters']"
            />

            <p class="mb-2 text-xs text-muted-foreground">
                <template v-if="totalCount">
                    {{
                        $t('trip.filters.resultRange', {
                            from: rangeFrom,
                            to: rangeTo,
                            total: totalCount,
                        })
                    }}
                </template>
                <template v-else>
                    {{ $t('trip.filters.resultEmpty') }}
                </template>
            </p>

            <TripTable
                :trips="items"
                :start-index="startIndex"
                :confirming-id="confirmingId"
                storage-key="admin-trip-columns"
                :default-columns="ADMIN_DEFAULT_TRIP_TABLE_COLUMNS"
                :table-actions="TABLE_ONLY_VIEW"
                @action="handleAction"
            />

            <TripPagination
                :links="links"
                :fallback-url="adminTripRoutes.index().url"
            />
        </CardContent>
    </Card>

    <!-- Dialog chính: view / create / edit -->
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
        :dialog-actions="DIALOG_ACTIONS"
        @action="handleAction"
        @preview="(url) => (previewImage = url)"
        @switch-mode="(m) => (mode = m)"
    />

    <TripDeleteDialog v-model:open="deleteOpen" :trip="target" />
    <TripReopenRequestDialog v-model:open="reopenRequestOpen" :trip="target" />
    <TripReopenReviewDialog v-model:open="reopenReviewOpen" :trip="target" :action="reviewAction" />
    <TripRejectDialog v-model:open="rejectTripOpen" :trip="target" />
    <ImageLightbox v-model="previewImage" />
</template>