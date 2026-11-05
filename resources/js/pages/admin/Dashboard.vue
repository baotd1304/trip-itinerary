<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CarFront,
    CircleDollarSign,
    Route,
    Tickets,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DashboardPeriodMenu from '@/components/dashboard/DashboardPeriodMenu.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import admin from '@/routes/admin';
const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'adminLayout.breadcrumbs.dashboard',
                href: admin.dashboard(),
            },
        ],
    },
});

type MonthlyStat = {
    month: string;
    tripCount: number;
    expenseTotal: number;
};

type CarDistance = {
    carId: number;
    licensePlate: string;
    distances: number[];
    tripCounts: number[];
    expenseTotals: number[];
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
    currentMonth: string;
    summary: {
        trips: number;
        expenses: number;
        distance: number;
        alerts: number;
    };
    distanceWarningThreshold: number;
}>();

const { locale } = useI18n();

type PeriodValueKey = 'tripCounts' | 'expenseTotals';

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

const coloredCarSeries = computed(() =>
    props.carDistanceSeries.map((car, carIndex) => ({
        ...car,
        color: chartColors[carIndex % chartColors.length],
    })),
);
const visibleCarIds = ref(
    new Set(props.carDistanceSeries.map((car) => car.carId)),
);

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
const currencyFormatter = computed(
    () =>
        new Intl.NumberFormat(
            locale.value === 'th'
                ? 'th-TH'
                : locale.value === 'en'
                  ? 'en-US'
                  : 'vi-VN',
            {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0,
            },
        ),
);

const recentMonthlyStats = computed(() => props.monthlyStats.slice(-12));
const selectedTripsPeriod = ref(`month:${props.currentMonth}`);
const selectedExpensesPeriod = ref(`month:${props.currentMonth}`);
const selectedDistancePeriod = ref(`month:${props.currentMonth}`);
const selectedSummaryAlertsPeriod = ref(`month:${props.currentMonth}`);
const currentYear = props.currentMonth.slice(0, 4);
const selectedTripsChartPeriod = ref(`year:${currentYear}`);
const selectedExpensesChartPeriod = ref(`year:${currentYear}`);
const selectedDistanceChartPeriod = ref(`year:${currentYear}`);
const selectedAlertListPeriod = ref(`month:${props.currentMonth}`);
const selectedPiePeriods = reactive({
    trips: `month:${props.currentMonth}`,
    expenses: `month:${props.currentMonth}`,
});

const monthLabel = (month: string, year = false) => {
    const [yearNumber, monthNumber] = month.split('-').map(Number);

    return new Intl.DateTimeFormat(locale.value, {
        month: 'short',
        ...(year ? { year: 'numeric' } : {}),
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(yearNumber, monthNumber - 1, 1)));
};

const monthOptions = computed(() =>
    recentMonthlyStats.value
        .slice()
        .reverse()
        .map((item) => ({
            value: `month:${item.month}`,
            label: monthLabel(item.month, true),
        })),
);
const yearOptions = computed(() =>
    Array.from({ length: 5 }, (_, index) => {
        const year = String(Number(props.currentMonth.slice(0, 4)) - index);

        return { value: `year:${year}`, label: year };
    }),
);

const periodLabel = (period: string) => {
    const [type, value] = period.split(':');

    return type === 'year' ? value : monthLabel(value, true);
};

const statsForPeriod = (period: string) => {
    const [type, value] = period.split(':');

    return type === 'year'
        ? props.monthlyStats.filter((item) =>
              item.month.startsWith(`${value}-`),
          )
        : props.monthlyStats.filter((item) => item.month === value);
};

const summaryForPeriod = (period: string) => {
    const selectedStats = statsForPeriod(period);
    const selectedIndexes = selectedStats.map((item) =>
        props.monthlyStats.indexOf(item),
    );

    return {
        trips: selectedStats.reduce((total, item) => total + item.tripCount, 0),
        expenses: selectedStats.reduce(
            (total, item) => total + item.expenseTotal,
            0,
        ),
        distance: props.carDistanceSeries.reduce(
            (total, car) =>
                total +
                selectedIndexes.reduce(
                    (carTotal, index) => carTotal + (car.distances[index] ?? 0),
                    0,
                ),
            0,
        ),
    };
};

const selectedSummaryAlerts = computed(() => {
    const selectedMonths = new Set(
        statsForPeriod(selectedSummaryAlertsPeriod.value).map(
            (item) => item.month,
        ),
    );

    return props.distanceAlerts.filter((alert) =>
        selectedMonths.has(alert.month),
    );
});

const selectedDistanceAlerts = computed(() => {
    const selectedMonths = new Set(
        statsForPeriod(selectedSummaryAlertsPeriod.value).map((item) => item.month),
    );

    return props.distanceAlerts.filter((alert) =>
        selectedMonths.has(alert.month),
    );
});

const scrollToAlerts = () => {
    document.getElementById('alertCars')?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const selectedPeriodPieCharts = computed(() => {
    const createChart = (
        key: PeriodValueKey,
        kind: 'trips' | 'expenses',
        title: string,
        period: string,
    ) => {
        const selectedIndexes = statsForPeriod(period).map((item) =>
            props.monthlyStats.indexOf(item),
        );
        const values = coloredCarSeries.value
            .map((car) => ({
                carId: car.carId,
                licensePlate: car.licensePlate,
                color: car.color,
                value: selectedIndexes.reduce(
                    (total, index) => total + (car[key][index] ?? 0),
                    0,
                ),
            }))
            .filter((car) => car.value > 0);
        const total = values.reduce((sum, car) => sum + car.value, 0);
        let currentPercentage = 0;
        const segments = values.map((car) => {
            const start = currentPercentage;
            const percentage = (car.value / total) * 100;
            currentPercentage += percentage;

            return {
                ...car,
                percentage,
                start,
                end: currentPercentage,
                label:
                    kind === 'trips'
                        ? numberFormatter.value.format(car.value)
                        : currencyFormatter.value.format(car.value),
            };
        });

        return {
            kind,
            period,
            title,
            total,
            segments,
            gradient: segments.length
                ? `conic-gradient(${segments.map((segment) => `${segment.color} ${segment.start}% ${segment.end}%`).join(', ')})`
                : '',
        };
    };

    return [
        createChart(
            'tripCounts',
            'trips',
            t('dashboard.tripsByVehicle'),
            selectedPiePeriods.trips,
        ),
        createChart(
            'expenseTotals',
            'expenses',
            t('dashboard.expensesByVehicle'),
            selectedPiePeriods.expenses,
        ),
    ];
});

const tripsChartStats = computed(() =>
    statsForPeriod(selectedTripsChartPeriod.value),
);
const expensesChartStats = computed(() =>
    statsForPeriod(selectedExpensesChartPeriod.value),
);

const maxTrips = computed(() =>
    Math.max(1, ...tripsChartStats.value.map((item) => item.tripCount)),
);
const maxExpenses = computed(() =>
    Math.max(1, ...expensesChartStats.value.map((item) => item.expenseTotal)),
);

const tripBarHeight = (value: number) => (value / maxTrips.value) * 100;
const expenseBarHeight = (value: number) => (value / maxExpenses.value) * 100;

const maxDistance = computed(() =>
    Math.max(
        props.distanceWarningThreshold + 500,
        ...props.carDistanceSeries.flatMap((car) =>
            statsForPeriod(selectedDistanceChartPeriod.value).map(
                (item) => car.distances[props.monthlyStats.indexOf(item)] ?? 0,
            ),
        ),
    ),
);

const distanceChart = computed(() => {
    const left = 64;
    const right = 948;
    const top = 20;
    const bottom = 226;
    const maximum = Math.ceil(maxDistance.value / 1000) * 1000;
    const chartStats = statsForPeriod(selectedDistanceChartPeriod.value);
    const monthCount = chartStats.length;
    const monthIndexes = chartStats.map((item) =>
        props.monthlyStats.indexOf(item),
    );
    const x = (index: number) =>
        left + (monthCount > 1 ? index / (monthCount - 1) : 0) * (right - left);
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
        lines: coloredCarSeries.value
            .filter((car) => visibleCarIds.value.has(car.carId))
            .map((car) => ({
                ...car,
                markers: monthIndexes.map((monthIndex, index) => ({
                    x: x(index),
                    y: y(car.distances[monthIndex] ?? 0),
                })),
                points: monthIndexes
                    .map(
                        (monthIndex, index) =>
                            `${x(index)},${y(car.distances[monthIndex] ?? 0)}`,
                    )
                    .join(' '),
            })),
        monthTicks: chartStats.map((item, index) => ({
            month: item.month,
            x: x(index),
        })),
    };
});
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <div
        class="mx-auto flex w-full max-w-screen-2xl flex-col gap-5 p-3 sm:gap-6 sm:p-4 md:p-6"
    >
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ $t('dashboard.title') }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ $t('dashboard.subtitle') }}
            </p>
        </div>

        <section :aria-label="$t('dashboard.summary')">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-2 pb-2"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <CardTitle class="text-sm font-medium">{{
                                $t('dashboard.summaryTrips')
                            }}</CardTitle>
                            <Tickets
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </div>
                        <DashboardPeriodMenu
                            v-model="selectedTripsPeriod"
                            :month-options="monthOptions"
                            :year-options="yearOptions"
                            :label="$t('dashboard.periodMenuLabel')"
                            :months-label="$t('dashboard.monthsGroup')"
                            :years-label="$t('dashboard.yearsGroup')"
                        />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">
                            {{
                                numberFormatter.format(
                                    summaryForPeriod(selectedTripsPeriod).trips,
                                )
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ periodLabel(selectedTripsPeriod) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-2 pb-2"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <CardTitle class="text-sm font-medium">{{
                                $t('dashboard.summaryExpenses')
                            }}</CardTitle>
                            <CircleDollarSign
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </div>
                        <DashboardPeriodMenu
                            v-model="selectedExpensesPeriod"
                            :month-options="monthOptions"
                            :year-options="yearOptions"
                            :label="$t('dashboard.periodMenuLabel')"
                            :months-label="$t('dashboard.monthsGroup')"
                            :years-label="$t('dashboard.yearsGroup')"
                        />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">
                            {{
                                currencyFormatter.format(
                                    summaryForPeriod(selectedExpensesPeriod)
                                        .expenses,
                                )
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ periodLabel(selectedExpensesPeriod) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-2 pb-2"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <CardTitle class="text-sm font-medium">{{
                                $t('dashboard.summaryDistance')
                            }}</CardTitle>
                            <Route
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </div>
                        <DashboardPeriodMenu
                            v-model="selectedDistancePeriod"
                            :month-options="monthOptions"
                            :year-options="yearOptions"
                            :label="$t('dashboard.periodMenuLabel')"
                            :months-label="$t('dashboard.monthsGroup')"
                            :years-label="$t('dashboard.yearsGroup')"
                        />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">
                            {{
                                numberFormatter.format(
                                    summaryForPeriod(selectedDistancePeriod)
                                        .distance,
                                )
                            }}
                            km
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ periodLabel(selectedDistancePeriod) }}
                        </p>
                    </CardContent>
                </Card>

                <Card
                    :class="
                        selectedSummaryAlerts.length
                            ? 'border-amber-500/50 bg-amber-500/5'
                            : ''
                    "
                >
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-2 pb-2"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <CardTitle class="text-sm font-medium">{{
                                $t('dashboard.summaryAlerts')
                            }}</CardTitle>
                            <AlertTriangle
                                class="size-4 shrink-0"
                                :class="
                                    selectedSummaryAlerts.length
                                        ? 'text-amber-600'
                                        : 'text-muted-foreground'
                                "
                            />
                        </div>
                        <DashboardPeriodMenu
                            v-model="selectedSummaryAlertsPeriod"
                            :month-options="monthOptions"
                            :year-options="yearOptions"
                            :label="$t('dashboard.periodMenuLabel')"
                            :months-label="$t('dashboard.monthsGroup')"
                            :years-label="$t('dashboard.yearsGroup')"
                        />
                    </CardHeader>
                    <CardContent>
                        <button
                            type="button"
                            class="block w-fit text-left text-2xl font-bold underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            aria-controls="alertCars"
                            @click="scrollToAlerts"
                        >
                            {{
                                numberFormatter.format(
                                    selectedSummaryAlerts.length,
                                )
                            }}
                        </button>
                        <p class="text-xs text-muted-foreground">
                            {{ periodLabel(selectedSummaryAlertsPeriod) }} ·
                            {{
                                $t('dashboard.alertThreshold', {
                                    distance: numberFormatter.format(
                                        distanceWarningThreshold,
                                    ),
                                })
                            }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader
                    class="flex flex-row items-start justify-between gap-2"
                >
                    <div class="min-w-0">
                        <CardTitle>{{
                            $t('dashboard.tripChartTitle')
                        }}</CardTitle>
                        <CardDescription>{{
                            periodLabel(selectedTripsChartPeriod)
                        }}</CardDescription>
                    </div>
                    <DashboardPeriodMenu
                        v-model="selectedTripsChartPeriod"
                        :month-options="monthOptions"
                        :year-options="yearOptions"
                        year-only
                        :label="$t('dashboard.periodMenuLabel')"
                        :months-label="$t('dashboard.monthsGroup')"
                        :years-label="$t('dashboard.yearsGroup')"
                    />
                </CardHeader>
                <CardContent class="px-3 sm:px-6">
                    <div class="overflow-x-auto">
                        <div
                            class="flex h-64 min-w-[min(100%,560px)] items-end gap-2 border-b border-border px-1 pt-4 sm:gap-3"
                        >
                            <div
                                v-for="item in tripsChartStats"
                                :key="item.month"
                                class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                                :title="`${monthLabel(item.month, true)}: ${numberFormatter.format(item.tripCount)}`"
                            >
                                <span
                                    class="text-[10px] text-muted-foreground sm:text-xs"
                                    >{{
                                        numberFormatter.format(item.tripCount)
                                    }}</span
                                >
                                <div class="flex w-full flex-1 items-end">
                                    <div
                                        class="w-full rounded-t bg-primary transition-[height]"
                                        :style="{
                                            height: `${tripBarHeight(item.tripCount)}%`,
                                        }"
                                    />
                                </div>
                                <span
                                    class="truncate text-[10px] text-muted-foreground sm:text-xs"
                                    >{{ monthLabel(item.month) }}</span
                                >
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-start justify-between gap-2"
                >
                    <div class="min-w-0">
                        <CardTitle>{{
                            $t('dashboard.expenseChartTitle')
                        }}</CardTitle>
                        <CardDescription>{{
                            periodLabel(selectedExpensesChartPeriod)
                        }}</CardDescription>
                    </div>
                    <DashboardPeriodMenu
                        v-model="selectedExpensesChartPeriod"
                        :month-options="monthOptions"
                        :year-options="yearOptions"
                        year-only
                        :label="$t('dashboard.periodMenuLabel')"
                        :months-label="$t('dashboard.monthsGroup')"
                        :years-label="$t('dashboard.yearsGroup')"
                    />
                </CardHeader>
                <CardContent class="px-3 sm:px-6">
                    <div class="overflow-x-auto">
                        <div
                            class="flex h-64 min-w-[min(100%,560px)] items-end gap-2 border-b border-border px-1 pt-4 sm:gap-3"
                        >
                            <div
                                v-for="item in expensesChartStats"
                                :key="item.month"
                                class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                                :title="`${monthLabel(item.month, true)}: ${currencyFormatter.format(item.expenseTotal)}`"
                            >
                                <span
                                    class="max-w-full truncate text-[10px] text-muted-foreground sm:text-xs"
                                >
                                    {{
                                        currencyFormatter.format(
                                            item.expenseTotal,
                                        )
                                    }}
                                </span>
                                <div class="flex w-full flex-1 items-end">
                                    <div
                                        class="w-full rounded-t bg-emerald-600 transition-[height]"
                                        :style="{
                                            height: `${expenseBarHeight(item.expenseTotal)}%`,
                                        }"
                                    />
                                </div>
                                <span
                                    class="truncate text-[10px] text-muted-foreground sm:text-xs"
                                    >{{ monthLabel(item.month) }}</span
                                >
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section :aria-label="$t('dashboard.vehicleCharts')">
            <div class="grid gap-6 xl:grid-cols-2">
                <Card
                    v-for="chart in selectedPeriodPieCharts"
                    :key="chart.kind"
                >
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-2"
                    >
                        <div class="min-w-0">
                            <CardTitle>{{ chart.title }}</CardTitle>
                            <CardDescription>{{
                                periodLabel(chart.period)
                            }}</CardDescription>
                        </div>
                        <DashboardPeriodMenu
                            v-model="selectedPiePeriods[chart.kind]"
                            :month-options="monthOptions"
                            :year-options="yearOptions"
                            :label="$t('dashboard.periodMenuLabel')"
                            :months-label="$t('dashboard.monthsGroup')"
                            :years-label="$t('dashboard.yearsGroup')"
                        />
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="chart.total > 0"
                            class="grid gap-5 sm:grid-cols-[auto_1fr] sm:items-center"
                        >
                            <div
                                class="mx-auto size-40 shrink-0 rounded-full border border-border"
                                role="img"
                                :aria-label="chart.title"
                                :style="{ background: chart.gradient }"
                            />
                            <ul class="grid gap-3">
                                <li
                                    v-for="car in chart.segments"
                                    :key="car.carId"
                                    class="flex min-w-0 items-center gap-2 text-sm"
                                >
                                    <span
                                        class="size-3 shrink-0 rounded-full"
                                        :style="{ backgroundColor: car.color }"
                                    />
                                    <span class="min-w-0 flex-1 truncate">{{
                                        car.licensePlate
                                    }}</span>
                                    <span
                                        class="shrink-0 text-right font-medium"
                                        >{{ car.label }}</span
                                    >
                                </li>
                            </ul>
                        </div>
                        <p
                            v-else
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            {{ $t('dashboard.noVehiclePeriodData') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <Card>
            <CardHeader class="flex flex-row items-start justify-between gap-2">
                <div class="min-w-0">
                    <CardTitle class="flex items-center gap-2">
                        <CarFront class="size-5 shrink-0" />
                        {{ $t('dashboard.distanceChartTitle') }}
                    </CardTitle>
                    <CardDescription>
                        {{ periodLabel(selectedDistanceChartPeriod) }} ·
                        {{
                            $t('dashboard.distanceChartDescription', {
                                distance: numberFormatter.format(
                                    distanceWarningThreshold,
                                ),
                            })
                        }}
                    </CardDescription>
                </div>
                <DashboardPeriodMenu
                    v-model="selectedDistanceChartPeriod"
                    :month-options="monthOptions"
                    :year-options="yearOptions"
                    year-only
                    :label="$t('dashboard.periodMenuLabel')"
                    :months-label="$t('dashboard.monthsGroup')"
                    :years-label="$t('dashboard.yearsGroup')"
                />
            </CardHeader>
            <CardContent>
                <div v-if="carDistanceSeries.length" class="overflow-x-auto">
                    <svg
                        viewBox="0 0 980 280"
                        class="w-full min-w-[760px]"
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
                            >
                                {{ numberFormatter.format(line.value) }}
                            </text>
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

                        <g
                            v-for="line in distanceChart.lines"
                            :key="line.carId"
                        >
                            <polyline
                                :points="line.points"
                                fill="none"
                                :stroke="line.color"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <title>{{ line.licensePlate }}</title>
                            </polyline>
                            <circle
                                v-for="(marker, index) in line.markers"
                                :key="`${line.carId}-${index}`"
                                :cx="marker.x"
                                :cy="marker.y"
                                r="3"
                                :fill="line.color"
                            />
                        </g>

                        <g
                            v-for="tick in distanceChart.monthTicks"
                            :key="tick.month"
                        >
                            <text
                                :x="tick.x"
                                y="252"
                                text-anchor="middle"
                                class="fill-muted-foreground text-[10px]"
                            >
                                {{ monthLabel(tick.month) }}
                            </text>
                        </g>
                    </svg>
                </div>
                <p
                    v-else
                    class="py-12 text-center text-sm text-muted-foreground"
                >
                    {{ $t('dashboard.noDistanceData') }}
                </p>

                <div
                    v-if="carDistanceSeries.length"
                    class="mt-5 flex flex-wrap gap-x-5 gap-y-2"
                >
                    <button
                        v-for="car in coloredCarSeries"
                        :key="car.carId"
                        type="button"
                        class="flex items-center gap-2 rounded-sm text-xs transition-opacity hover:opacity-75 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            visibleCarIds.has(car.carId)
                                ? ''
                                : 'line-through opacity-40'
                        "
                        :aria-pressed="visibleCarIds.has(car.carId)"
                        :aria-label="
                            $t('dashboard.toggleVehicleSeries', {
                                vehicle: car.licensePlate,
                            })
                        "
                        :title="
                            $t('dashboard.toggleVehicleSeries', {
                                vehicle: car.licensePlate,
                            })
                        "
                        @click="toggleCarSeries(car.carId)"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :style="{ backgroundColor: car.color }"
                        />
                        <span>{{ car.licensePlate }}</span>
                    </button>
                    <div class="flex items-center gap-2 text-xs">
                        <span
                            class="w-4 border-t-2 border-dashed border-amber-600"
                        />
                        <span>{{
                            $t('dashboard.warningThresholdLegend', {
                                distance: numberFormatter.format(
                                    distanceWarningThreshold,
                                ),
                            })
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card id="alertCars" class="scroll-mt-1">
            <CardHeader class="flex flex-row items-start justify-between gap-2">
                <div class="min-w-0">
                    <CardTitle class="flex items-center gap-2">
                        <AlertTriangle class="size-5 shrink-0 text-amber-600" />
                        {{ $t('dashboard.distanceWarningsTitle') }}
                    </CardTitle>
                    <CardDescription>
                        {{ periodLabel(selectedSummaryAlertsPeriod) }} ·
                        {{
                            $t('dashboard.distanceWarningsDescription', {
                                distance: numberFormatter.format(
                                    distanceWarningThreshold,
                                ),
                            })
                        }}
                    </CardDescription>
                </div>
                <DashboardPeriodMenu
                    v-model="selectedSummaryAlertsPeriod"
                    :month-options="monthOptions"
                    :year-options="yearOptions"
                    :label="$t('dashboard.periodMenuLabel')"
                    :months-label="$t('dashboard.monthsGroup')"
                    :years-label="$t('dashboard.yearsGroup')"
                />
            </CardHeader>
            <CardContent>
                <div v-if="selectedDistanceAlerts.length">
                    <ul class="grid gap-3 md:hidden">
                        <li
                            v-for="alert in selectedDistanceAlerts"
                            :key="`${alert.carId}-${alert.month}`"
                            class="grid grid-cols-2 gap-3 rounded-lg border p-3 text-sm"
                        >
                            <span class="text-muted-foreground">{{
                                $t('dashboard.vehicleColumn')
                            }}</span>
                            <span class="text-right font-medium">{{
                                alert.licensePlate
                            }}</span>
                            <span class="text-muted-foreground">{{
                                $t('dashboard.monthColumn')
                            }}</span>
                            <span class="text-right">{{
                                monthLabel(alert.month, true)
                            }}</span>
                            <span class="text-muted-foreground">{{
                                $t('dashboard.distanceColumn')
                            }}</span>
                            <span
                                class="text-right font-semibold text-amber-700 dark:text-amber-400"
                            >
                                {{ numberFormatter.format(alert.distance) }} km
                            </span>
                        </li>
                    </ul>
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            id="alertcars"
                            class="w-full min-w-[420px] text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b text-left text-muted-foreground"
                                >
                                    <th class="px-3 py-2 font-medium">
                                        {{ $t('dashboard.vehicleColumn') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        {{ $t('dashboard.monthColumn') }}
                                    </th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        {{ $t('dashboard.distanceColumn') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="alert in selectedDistanceAlerts"
                                    :key="`${alert.carId}-${alert.month}`"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-3 py-3 font-medium">
                                        {{ alert.licensePlate }}
                                    </td>
                                    <td class="px-3 py-3">
                                        {{ monthLabel(alert.month, true) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right font-semibold text-amber-700 dark:text-amber-400"
                                    >
                                        {{
                                            numberFormatter.format(
                                                alert.distance,
                                            )
                                        }}
                                        km
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    {{ $t('dashboard.noDistanceWarnings') }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
