<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CarBulkDialog from '@/components/cars/CarBulkDialog.vue';
import CarDeleteDialog from '@/components/cars/CarDeleteDialog.vue';
import CarFilters from '@/components/cars/CarFilters.vue';
import CarFormDialog from '@/components/cars/CarFormDialog.vue';
import CarTable from '@/components/cars/CarTable.vue';
import TripPagination from '@/components/trips/TripPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCarDialogs } from '@/composables/useCarDialogs';
import { usePagination } from '@/composables/usePagination';
import type { PaginationMeta } from '@/composables/usePagination';
import { toArray } from '@/lib/array';
import { adminCarRoutes } from '@/lib/car-routes-admin';
import admin from '@/routes/admin';
import type { AppCar, CarBulkAction, CarDialogMode } from '@/types/car';
import type { PaginationLink } from '@/types/trip';

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'adminLayout.breadcrumbs.cars',
                href: admin.cars.index(),
            },
        ],
    },
});

interface CarFiltersState {
    search?: string | null;
    brand?: string | null;
    is_active?: '1' | '0' | null;
    sort?: string | null;
    dir?: 'asc' | 'desc' | null;
}

const props = defineProps<{
    cars: { data?: AppCar[]; links?: PaginationLink[]; meta?: { links?: PaginationLink[] } } | AppCar[];
    carsMeta?: PaginationMeta;
    brands?: string[];
    stats?: {
        total?: number;
        active?: number;
        inactive?: number;
    };
    filters?: CarFiltersState;
    can?: {
        create?: boolean;
        toggleActive?: boolean;
        delete?: boolean;
    };
}>();

const carFilters = ref<InstanceType<typeof CarFilters> | null>(null);
const items = computed(() => toArray<AppCar>(props.cars));
const brands = computed(() => props.brands ?? []);
const links = computed<PaginationLink[]>(() =>
    Array.isArray(props.cars) ? [] : props.cars.links ?? props.cars.meta?.links ?? [],
);
const { total: totalCount, startIndex, rangeFrom, rangeTo } = usePagination(
    computed(() => props.cars),
    computed(() => props.carsMeta),
    computed(() => items.value.length),
);
const {
    mainOpen,
    mode,
    activeCar,
    deleteOpen,
    target,
    processingId,
    openCreate,
    handleAction,
} = useCarDialogs();

const hasStats = computed(() =>
    !!props.stats && Object.values(props.stats).some((value) => (value ?? 0) > 0),
);

const selected = ref<number[]>([]);
const bulkOpen = ref(false);
const bulkAction = ref<CarBulkAction>('activate');
const bulkProcessing = ref(false);

const openBulk = (action: CarBulkAction) => {
    if (!selected.value.length || bulkProcessing.value) {
        return;
    }

    bulkAction.value = action;
    bulkOpen.value = true;
};

const runBulk = () => {
    if (!selected.value.length || bulkProcessing.value) {
        return;
    }

    bulkProcessing.value = true;
    router.post(
        adminCarRoutes.bulk().url,
        { action: bulkAction.value, car_ids: selected.value },
        {
            preserveScroll: true,
            only: ['cars', 'carsMeta', 'stats', 'brands', 'filters'],
            onSuccess: () => {
                selected.value = [];
                bulkOpen.value = false;
            },
            onFinish: () => {
                bulkProcessing.value = false;
            },
        },
    );
};

const setMode = (nextMode: CarDialogMode) => {
    mode.value = nextMode;
};
</script>

<template>
    <Head :title="t('car.admin.pageTitle')" />

    <Card class="overflow-hidden">
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <CardTitle>{{ $t('car.admin.listTitle') }}</CardTitle>
                    <div v-if="hasStats" class="flex flex-wrap items-center gap-1.5">
                        <Badge variant="secondary">
                            {{ $t('car.stats.total') }}: {{ props.stats?.total }}
                        </Badge>
                        <Badge
                            v-if="props.stats?.active"
                            as="button"
                            type="button"
                            :aria-pressed="props.filters?.is_active === '1'"
                            class="cursor-pointer bg-emerald-600 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="carFilters?.applyStatsFilter('active')"
                        >
                            {{ $t('car.status.active') }}: {{ props.stats.active }}
                        </Badge>
                        <Badge
                            v-if="props.stats?.inactive"
                            as="button"
                            type="button"
                            :aria-pressed="props.filters?.is_active === '0'"
                            class="cursor-pointer bg-gray-500 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="carFilters?.applyStatsFilter('inactive')"
                        >
                            {{ $t('car.status.inactive') }}: {{ props.stats.inactive }}
                        </Badge>
                    </div>
                </div>
                <Button v-if="props.can?.create" type="button" @click="openCreate">
                    {{ $t('car.create') }}
                </Button>
            </div>
        </CardHeader>

        <CardContent>
            <CarFilters
                ref="carFilters"
                :url="adminCarRoutes.index().url"
                :initial="props.filters"
                :brands="brands"
                :only="['cars', 'carsMeta', 'stats', 'brands', 'filters']"
            />
            <div class="flex flex-wrap items-center justify-between gap-2 py-2">
                <p class="text-xs text-muted-foreground">
                    <template v-if="totalCount">
                        {{ $t('car.filters.resultRange', { from: rangeFrom, to: rangeTo, total: totalCount }) }}
                    </template>
                    <template v-else>
                        {{ $t('car.filters.resultEmpty') }}
                    </template>
                </p>
                <div v-if="selected.length" class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-muted-foreground">
                        {{ $t('common.selectedCount', { count: selected.length }) }}
                    </span>
                    <Button
                        v-if="props.can?.toggleActive"
                        size="xs"
                        variant="outline"
                        :disabled="bulkProcessing"
                        @click="openBulk('activate')"
                    >
                        {{ $t('car.actions.activate') }}
                    </Button>
                    <Button
                        v-if="props.can?.toggleActive"
                        size="xs"
                        variant="outline"
                        :disabled="bulkProcessing"
                        @click="openBulk('deactivate')"
                    >
                        {{ $t('car.actions.deactivate') }}
                    </Button>
                    <Button
                        v-if="props.can?.delete"
                        size="xs"
                        variant="destructive"
                        :disabled="bulkProcessing"
                        @click="openBulk('delete')"
                    >
                        {{ $t('common.delete') }}
                    </Button>
                </div>
            </div>

            <CarTable
                v-model:selected="selected"
                :cars="items"
                :start-index="startIndex"
                :processing-id="processingId"
                :bulk-processing="bulkProcessing"
                @action="handleAction"
            />

            <TripPagination :links="links" :fallback-url="adminCarRoutes.index().url" />
        </CardContent>
    </Card>

    <CarFormDialog
        v-model:open="mainOpen"
        :mode="mode"
        :car="activeCar"
        @switch-mode="setMode"
    />
    <CarDeleteDialog v-model:open="deleteOpen" :car="target" />
    <CarBulkDialog
        v-model:open="bulkOpen"
        :action="bulkAction"
        :count="selected.length"
        :processing="bulkProcessing"
        @confirm="runBulk"
    />
</template>
