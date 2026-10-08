<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BanknoteArrowDown, Car, MapPin, Tickets, Users } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useFormat } from '@/composables/useFormat';

type Trip = {
    id: number;
    name: string;
    destination: string | null;
    start_date: string | null;
    end_date: string | null;
    status: string | null;
    total_expense: number | null;
};

type Stats = {
    trips: number;
    cars: number;
    members: number;
};

const { latestTrips = [], stats } = defineProps<{
    latestTrips?: Trip[];
    stats?: Stats;
}>();

const { t, te } = useI18n();
const { formatDate, formatMoney } = useFormat();

const features = [
    {
        title: 'home.features.itinerary.title',
        description: 'home.features.itinerary.description',
        icon: Tickets,
    },
    {
        title: 'home.features.vehicles.title',
        description: 'home.features.vehicles.description',
        icon: Car,
    },
    {
        title: 'home.features.expenses.title',
        description: 'home.features.expenses.description',
        icon: BanknoteArrowDown,
    },
];

const formatTripDate = (value: string | null) => formatDate(value) || t('common.none');
const translateTripStatus = (status: string) => {
    const key = `trip.status.${status}`;

    return te(key) ? t(key) : status;
};
</script>

<template>
    <Head :title="t('home.pageTitle')" />
        <!-- Hero -->
        <section class="relative overflow-hidden border-b border-sidebar-border/70">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-muted),transparent_60%)]" />

            <div class="relative mx-auto w-full max-w-7xl px-4 py-20 md:px-6 md:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <Badge variant="secondary" class="mb-4">Trip Itinerary</Badge>

                    <h1 class="text-4xl font-semibold tracking-tight text-balance md:text-5xl">
                        {{ $t('home.hero.title') }}
                    </h1>

                    <p class="mt-5 text-lg text-muted-foreground text-pretty">
                        {{ $t('home.hero.description') }}
                    </p>

                    <!-- <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Button as-child size="lg">
                            <Link :href="register()">
                                Đăng ký
                                <ArrowRight class="ml-1 size-4" />
                            </Link>
                        </Button>
                        <Button as-child size="lg" variant="outline">
                            <Link :href="login()">Đăng nhập</Link>
                        </Button>
                    </div> -->
                </div>

                <!-- Stats -->
                <div v-if="stats" class="mx-auto mt-16 grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card class="text-center">
                        <CardContent class="pt-6">
                            <Tickets class="mx-auto mb-2 size-5 text-muted-foreground" />
                            <p class="text-3xl font-semibold">{{ stats.trips }}</p>
                            <p class="text-sm text-muted-foreground">{{ $t('home.stats.trips') }}</p>
                        </CardContent>
                    </Card>
                    <Card class="text-center">
                        <CardContent class="pt-6">
                            <Car class="mx-auto mb-2 size-5 text-muted-foreground" />
                            <p class="text-3xl font-semibold">{{ stats.cars }}</p>
                            <p class="text-sm text-muted-foreground">{{ $t('home.stats.vehicles') }}</p>
                        </CardContent>
                    </Card>
                    <Card class="text-center">
                        <CardContent class="pt-6">
                            <Users class="mx-auto mb-2 size-5 text-muted-foreground" />
                            <p class="text-3xl font-semibold">{{ stats.members }}</p>
                            <p class="text-sm text-muted-foreground">{{ $t('home.stats.members') }}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto w-full max-w-7xl px-4 py-16 md:px-6 md:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight">{{ $t('home.features.title') }}</h2>
                <p class="mt-3 text-muted-foreground">{{ $t('home.features.description') }}</p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <Card v-for="feature in features" :key="feature.title" class="h-full">
                    <CardHeader>
                        <div class="mb-2 flex size-10 items-center justify-center rounded-md bg-muted">
                            <component :is="feature.icon" class="size-5" />
                        </div>
                        <CardTitle>{{ $t(feature.title) }}</CardTitle>
                        <CardDescription>{{ $t(feature.description) }}</CardDescription>
                    </CardHeader>
                </Card>
            </div>
        </section>

        <!-- Latest trips -->
        <section class="border-t border-sidebar-border/70 bg-muted/30">
            <div class="mx-auto w-full max-w-7xl px-4 py-16 md:px-6 md:py-20">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="text-3xl font-semibold tracking-tight">{{ $t('home.latestTrips.title') }}</h2>
                        <p class="mt-2 text-muted-foreground">{{ $t('home.latestTrips.description') }}</p>
                    </div>
                    <Button as-child variant="ghost">
                        <Link href="/trips">
                            {{ $t('home.latestTrips.viewAll') }}
                            <ArrowRight class="ml-1 size-4" />
                        </Link>
                    </Button>
                </div>

                <div v-if="latestTrips.length" class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="trip in latestTrips" :key="trip.id" class="flex h-full flex-col">
                        <CardHeader>
                            <div class="flex items-start justify-between gap-3">
                                <CardTitle class="line-clamp-1">{{ trip.name }}</CardTitle>
                                <Badge v-if="trip.status" variant="secondary">{{ translateTripStatus(trip.status) }}</Badge>
                            </div>
                            <CardDescription class="flex items-center gap-1">
                                <MapPin class="size-3.5" />
                                {{ trip.destination ?? $t('home.latestTrips.destinationFallback') }}
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="mt-auto space-y-2 text-sm">
                            <div class="flex justify-between text-muted-foreground">
                                <span>{{ $t('home.latestTrips.time') }}</span>
                                <span class="font-medium text-foreground">
                                    {{ formatTripDate(trip.start_date) }} – {{ formatTripDate(trip.end_date) }}
                                </span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>{{ $t('home.latestTrips.expense') }}</span>
                                <span class="font-medium text-foreground">{{ formatMoney(trip.total_expense) }}</span>
                            </div>

                            <Button as-child variant="outline" class="mt-4 w-full">
                                <Link :href="`/trips/${trip.id}`">{{ $t('home.latestTrips.details') }}</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else class="mt-8">
                    <CardContent class="flex flex-col items-center justify-center gap-2 py-14 text-center">
                        <Tickets class="size-8 text-muted-foreground" />
                        <p class="font-medium">{{ $t('home.latestTrips.emptyTitle') }}</p>
                        <p class="text-sm text-muted-foreground">{{ $t('home.latestTrips.emptyDescription') }}</p>
                    </CardContent>
                </Card>
            </div>
        </section>

</template>