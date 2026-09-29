<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

import TripTable from '@/components/trips/TripTable.vue';
import TripPagination from '@/components/trips/TripPagination.vue';
import TripFormDialog from '@/components/trips/TripFormDialog.vue';
import TripDeleteDialog from '@/components/trips/TripDeleteDialog.vue';
import TripReopenRequestDialog from '@/components/trips/TripReopenRequestDialog.vue';
import TripReopenReviewDialog from '@/components/trips/TripReopenReviewDialog.vue';
import TripRejectDialog from '@/components/trips/TripRejectDialog.vue';
import ImageLightbox from '@/components/trips/ImageLightbox.vue';
import TripFilters from '@/components/trips/TripFilters.vue';

import trips from '@/routes/client/trips';
import { toArray } from '@/lib/array';
import { useTripDialogs } from '@/composables/useTripDialogs';
import type { Advisor, Car, Driver, PaginationLink, Trip, TripStatus } from '@/types/trip';
import { usePagination, type PaginationMeta } from '@/composables/usePagination';

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
    filters?: {
        search?: string | null;
        status?: TripStatus | null;
        from?: string | null;
        to?: string | null;
    };
    can?: { create?: boolean };
}>();

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
} = useTripDialogs();

</script>

<template>
    <Head :title="t('trip.pageTitle')" />

    <Card class="overflow-hidden">
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <CardTitle>{{ $t('trip.listTitle') }}</CardTitle>
                <div class="flex items-center gap-3">
                    <Button v-if="props.can?.create" @click="openCreate" variant="default">
                        {{ $t('trip.create') }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Thanh tìm kiếm / lọc -->
            <TripFilters :url="trips.index().url" :initial="props.filters" :only="['trips', 'filters']"/>
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
            <TripTable :trips="items" :start-index="startIndex" :confirming-id="confirmingId" @action="handleAction" />
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

    <ImageLightbox v-model="previewImage" />
</template>