<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, CarFront, CircleDollarSign, Route, Tickets } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type MonthlyStat = {
    month: string;
    tripCount: number;
    expenseTotal: number;
};

type CarDistance = {
    carId: number;
    licensePlate: string;
    distances: number[];
};

type DistanceAlert = {
    carId: number;
    licensePlate: string;
    month: string;
    distance: number;
};

const props = defineProps<{
    monthlyStats: MonthlyStat[];
    carDistanceSeries: CarDistance[];
    distanceAlerts: DistanceAlert[];
    summary: {
        trips: number;
        expenses: number;
        distance: number;
        alerts: number;
    };
    distanceWarningThreshold: number;
}>();

const { locale } = useI18n();

defineOptions({
    // layout: {
    //     breadcrumbs: [
    //         {
    //             title: 'Dashboard',
    //             href: dashboard(),
    //         },
    //     ],
    // },
    layout: undefined, // giữ layout hiện tại
});

const chartColors = [
    '#2563eb',
    '#16a34a',
    '#9333ea',
    '#ea580c',
    '#0891b2',
    '#db2777',
    '#4f46e5',
    '#65a30d',
    '#dc2626',
    '#0d9488',
];

const coloredCarSeries = computed(() => props.carDistanceSeries.map((car, carIndex) => ({
    ...car,
    color: chartColors[carIndex % chartColors.length],
})));
const visibleCarIds = ref(new Set(props.carDistanceSeries.map((car) => car.carId)));

const toggleCarSeries = (carId: number) => {
    const updatedVisibleCarIds = new Set(visibleCarIds.value);

    if (updatedVisibleCarIds.has(carId)) {
        updatedVisibleCarIds.delete(carId);
    } else {
        updatedVisibleCarIds.add(carId);
    }

    visibleCarIds.value = updatedVisibleCarIds;
};

const numberFormatter = computed(() => new Intl.NumberFormat(locale.value));
const currencyFormatter = computed(() => new Intl.NumberFormat(locale.value === 'th' ? 'th-TH' : locale.value === 'en' ? 'en-US' : 'vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
}));

const monthLabel = (month: string, year = false) => {
    const [yearNumber, monthNumber] = month.split('-').map(Number);

    return new Intl.DateTimeFormat(locale.value, {
        month: 'short',
        ...(year ? { year: 'numeric' } : {}),
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(yearNumber, monthNumber - 1, 1)));
};

const maxTrips = computed(() => Math.max(1, ...props.monthlyStats.map((item) => item.tripCount)));
const maxExpenses = computed(() => Math.max(1, ...props.monthlyStats.map((item) => item.expenseTotal)));

const tripBarHeight = (value: number) => (value / maxTrips.value) * 100;
const expenseBarHeight = (value: number) => (value / maxExpenses.value) * 100;

const maxDistance = computed(() => Math.max(
    props.distanceWarningThreshold + 500,
    ...props.carDistanceSeries.flatMap((car) => car.distances),
));

const distanceChart = computed(() => {
    const left = 64;
    const right = 948;
    const top = 20;
    const bottom = 226;
    const maximum = Math.ceil(maxDistance.value / 1000) * 1000;
    const monthCount = props.monthlyStats.length;
    const x = (index: number) => left + (monthCount > 1 ? index / (monthCount - 1) : 0) * (right - left);
    const y = (value: number) => bottom - (value / maximum) * (bottom - top);

    return {
        left,
        right,
        top,
        bottom,
        maximum,
        thresholdY: y(props.distanceWarningThreshold),
        grid: Array.from({ length: 5 }, (_, index) => {
            const value = (maximum / 4) * index;

            return { value, y: y(value) };
        }),
        lines: coloredCarSeries.value.filter((car) => visibleCarIds.value.has(car.carId)).map((car) => ({
            ...car,
            points: car.distances.map((distance, index) => `${x(index)},${y(distance)}`).join(' '),
        })),
        monthTicks: props.monthlyStats.map((item, index) => ({
            month: item.month,
            x: x(index),
        })),
    };
});
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $t('dashboard.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ $t('dashboard.subtitle') }}</p>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" :aria-label="$t('dashboard.summary')">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-4 pb-2">
                    <CardTitle class="text-sm font-medium">{{ $t('dashboard.summaryTrips') }}</CardTitle>
                    <Tickets class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ numberFormatter.format(summary.trips) }}</p>
                    <p class="text-xs text-muted-foreground">{{ $t('dashboard.last12Months') }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-4 pb-2">
                    <CardTitle class="text-sm font-medium">{{ $t('dashboard.summaryExpenses') }}</CardTitle>
                    <CircleDollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ currencyFormatter.format(summary.expenses) }}</p>
                    <p class="text-xs text-muted-foreground">{{ $t('dashboard.last12Months') }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-4 pb-2">
                    <CardTitle class="text-sm font-medium">{{ $t('dashboard.summaryDistance') }}</CardTitle>
                    <Route class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ numberFormatter.format(summary.distance) }} km</p>
                    <p class="text-xs text-muted-foreground">{{ $t('dashboard.last12Months') }}</p>
                </CardContent>
            </Card>

            <Card :class="summary.alerts ? 'border-amber-500/50 bg-amber-500/5' : ''">
                <CardHeader class="flex flex-row items-center justify-between gap-4 pb-2">
                    <CardTitle class="text-sm font-medium">{{ $t('dashboard.summaryAlerts') }}</CardTitle>
                    <AlertTriangle class="size-4" :class="summary.alerts ? 'text-amber-600' : 'text-muted-foreground'" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ numberFormatter.format(summary.alerts) }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ $t('dashboard.alertThreshold', { distance: numberFormatter.format(distanceWarningThreshold) }) }}
                    </p>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('dashboard.tripChartTitle') }}</CardTitle>
                    <CardDescription>{{ $t('dashboard.monthlyChartDescription') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex h-64 items-end gap-2 border-b border-border px-1 pt-4 sm:gap-3">
                        <div
                            v-for="item in monthlyStats"
                            :key="item.month"
                            class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                            :title="`${monthLabel(item.month, true)}: ${numberFormatter.format(item.tripCount)}`"
                        >
                            <span class="text-[10px] text-muted-foreground sm:text-xs">{{ numberFormatter.format(item.tripCount) }}</span>
                            <div class="flex w-full flex-1 items-end">
                                <div
                                    class="w-full rounded-t bg-primary transition-[height]"
                                    :style="{ height: `${tripBarHeight(item.tripCount)}%` }"
                                />
                            </div>
                            <span class="truncate text-[10px] text-muted-foreground sm:text-xs">{{ monthLabel(item.month) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('dashboard.expenseChartTitle') }}</CardTitle>
                    <CardDescription>{{ $t('dashboard.monthlyChartDescription') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex h-64 items-end gap-2 border-b border-border px-1 pt-4 sm:gap-3">
                        <div
                            v-for="item in monthlyStats"
                            :key="item.month"
                            class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                            :title="`${monthLabel(item.month, true)}: ${currencyFormatter.format(item.expenseTotal)}`"
                        >
                            <span class="max-w-full truncate text-[10px] text-muted-foreground sm:text-xs">
                                {{ currencyFormatter.format(item.expenseTotal) }}
                            </span>
                            <div class="flex w-full flex-1 items-end">
                                <div
                                    class="w-full rounded-t bg-emerald-600 transition-[height]"
                                    :style="{ height: `${expenseBarHeight(item.expenseTotal)}%` }"
                                />
                            </div>
                            <span class="truncate text-[10px] text-muted-foreground sm:text-xs">{{ monthLabel(item.month) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CarFront class="size-5" />
                    {{ $t('dashboard.distanceChartTitle') }}
                </CardTitle>
                <CardDescription>
                    {{ $t('dashboard.distanceChartDescription', { distance: numberFormatter.format(distanceWarningThreshold) }) }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="carDistanceSeries.length" class="overflow-x-auto">
                    <svg
                        viewBox="0 0 980 280"
                        class="min-w-[760px] w-full"
                        role="img"
                        :aria-label="$t('dashboard.distanceChartTitle')"
                    >
                        <g v-for="line in distanceChart.grid" :key="line.value">
                            <line
                                :x1="distanceChart.left"
                                :x2="distanceChart.right"
                                :y1="line.y"
                                :y2="line.y"
                                stroke="currentColor"
                                class="text-border"
                            />
                            <text
                                x="54"
                                :y="line.y - 5"
                                text-anchor="end"
                                class="fill-muted-foreground text-[10px]"
                            >{{ numberFormatter.format(line.value) }}</text>
                        </g>

                        <line
                            :x1="distanceChart.left"
                            :x2="distanceChart.right"
                            :y1="distanceChart.thresholdY"
                            :y2="distanceChart.thresholdY"
                            stroke="#d97706"
                            stroke-dasharray="6 4"
                            stroke-width="2"
                        />

                        <polyline
                            v-for="line in distanceChart.lines"
                            :key="line.carId"
                            :points="line.points"
                            fill="none"
                            :stroke="line.color"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <title>{{ line.licensePlate }}</title>
                        </polyline>

                        <g v-for="tick in distanceChart.monthTicks" :key="tick.month">
                            <text
                                :x="tick.x"
                                y="252"
                                text-anchor="middle"
                                class="fill-muted-foreground text-[10px]"
                            >{{ monthLabel(tick.month) }}</text>
                        </g>
                    </svg>
                </div>
                <p v-else class="py-12 text-center text-sm text-muted-foreground">
                    {{ $t('dashboard.noDistanceData') }}
                </p>

                <div v-if="carDistanceSeries.length" class="mt-5 flex flex-wrap gap-x-5 gap-y-2">
                    <button
                        v-for="car in coloredCarSeries"
                        :key="car.carId"
                        type="button"
                        class="flex items-center gap-2 rounded-sm text-xs transition-opacity hover:opacity-75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="visibleCarIds.has(car.carId) ? '' : 'opacity-40 line-through'"
                        :aria-pressed="visibleCarIds.has(car.carId)"
                        :aria-label="$t('dashboard.toggleVehicleSeries', { vehicle: car.licensePlate })"
                        :title="$t('dashboard.toggleVehicleSeries', { vehicle: car.licensePlate })"
                        @click="toggleCarSeries(car.carId)"
                    >
                        <span class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: car.color }" />
                        <span>{{ car.licensePlate }}</span>
                    </button>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-4 border-t-2 border-dashed border-amber-600" />
                        <span>{{ $t('dashboard.warningThresholdLegend', { distance: numberFormatter.format(distanceWarningThreshold) }) }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <AlertTriangle class="size-5 text-amber-600" />
                    {{ $t('dashboard.distanceWarningsTitle') }}
                </CardTitle>
                <CardDescription>
                    {{ $t('dashboard.distanceWarningsDescription', { distance: numberFormatter.format(distanceWarningThreshold) }) }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="distanceAlerts.length" class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="px-3 py-2 font-medium">{{ $t('dashboard.vehicleColumn') }}</th>
                                <th class="px-3 py-2 font-medium">{{ $t('dashboard.monthColumn') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ $t('dashboard.distanceColumn') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="alert in distanceAlerts" :key="`${alert.carId}-${alert.month}`" class="border-b last:border-0">
                                <td class="px-3 py-3 font-medium">{{ alert.licensePlate }}</td>
                                <td class="px-3 py-3">{{ monthLabel(alert.month, true) }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-amber-700 dark:text-amber-400">
                                    {{ numberFormatter.format(alert.distance) }} km
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="py-8 text-center text-sm text-muted-foreground">
                    {{ $t('dashboard.noDistanceWarnings') }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
