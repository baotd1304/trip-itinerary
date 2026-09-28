<script setup lang="ts">
import { computed } from 'vue';
import { CircleCheckBigIcon, CircleX } from '@lucide/vue';
import { cloudinaryThumb, useFormat } from '@/composables/useFormat';
import type { Car, TripFormModel, TripImage } from '@/types/trip';

const props = defineProps<{ model: TripFormModel; images: TripImage[]; cars: Car[] }>();
defineEmits<{ preview: [string] }>();

const { formatDate, formatMoney } = useFormat();

const carLabel = computed(() => {
    const c = props.cars.find((x) => x.id === Number(props.model.car_id));
    return c ? `${c.id} - ${c.license_plate}` : '—';
});
</script>

<template>
    <div class="space-y-4">
        <!-- Thông tin chung -->
        <section class="rounded-md border p-3">
            <h3 class="mb-3 text-sm font-semibold">{{ $t('trip.detail.generalInfo') }}</h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.advisor') }}</dt>
                    <dd class="font-medium">{{ model.advisor?.name ?? $t('common.none') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.driver') }}</dt>
                    <dd class="font-medium">{{ model.driver?.name ?? $t('common.none') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.car') }}</dt>
                    <dd class="font-medium">{{ carLabel }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.day') }}</dt>
                    <dd class="font-medium">{{ formatDate(model.day) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.departureTime') }}</dt>
                    <dd class="font-medium">{{ model.departure_time || $t('common.none') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.arrivalTime') }}</dt>
                    <dd class="font-medium">{{ model.arrival_time || $t('common.none') }}</dd>
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.detail.route') }}</dt>
                    <dd class="font-medium">{{ model.origin }} → {{ model.destination }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.odoStart') }}</dt>
                    <dd class="font-medium">{{ model.odo_start }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.odoEnd') }}</dt>
                    <dd class="font-medium">{{ model.odo_end }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.table.distance') }}</dt>
                    <dd class="font-medium">{{ $t('trip.detail.distanceKm', { value: model.distance }) }}</dd>
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-xs text-muted-foreground">{{ $t('common.note') }}</dt>
                    <dd class="font-medium">{{ model.note || $t('common.none') }}</dd>
                </div>
            </dl>
        </section>

        <!-- Chi phí -->
        <section class="rounded-md border p-3">
            <h3 class="mb-3 text-sm font-semibold">{{ $t('trip.detail.expenses') }}</h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.overtime') }}</dt>
                    <dd class="font-medium">{{ $t('trip.detail.overtimeHours', { count: model.overtime }) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.tollFee') }}</dt>
                    <dd class="font-medium">{{ formatMoney(model.toll_fee) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.airportFee') }}</dt>
                    <dd class="font-medium">{{ formatMoney(model.airport_fee) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.overnight') }}</dt>
                    <dd class="flex items-center gap-1 font-medium">
                        <CircleCheckBigIcon v-if="model.is_overnight" class="h-4 w-4 text-green-500" />
                        <CircleX v-else class="h-4 w-4 text-gray-400" />
                        {{ model.is_overnight ? $t('common.yes') : $t('common.no') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.form.holiday') }}</dt>
                    <dd class="flex items-center gap-1 font-medium">
                        <CircleCheckBigIcon v-if="model.is_holiday" class="h-4 w-4 text-green-500" />
                        <CircleX v-else class="h-4 w-4 text-gray-400" />
                        {{ model.is_holiday ? $t('common.yes') : $t('common.no') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $t('trip.detail.totalFee') }}</dt>
                    <dd class="text-base font-semibold text-emerald-700 dark:text-emerald-400">
                        {{ formatMoney(model.total_fee) }}
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Hình ảnh -->
        <section class="rounded-md border p-3">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold">{{ $t('trip.images.label') }}</h3>
                <span class="text-xs text-muted-foreground">
                    {{ $t('trip.images.countOnly', { count: images.length }) }}
                </span>
            </div>

            <div v-if="images.length" class="grid grid-cols-4 gap-3 sm:grid-cols-5">
                <div v-for="img in images" :key="'view-' + img.id"
                     class="aspect-square overflow-hidden rounded-md border">
                    <img :src="cloudinaryThumb(img.url)" :alt="$t('trip.images.alt', { id: img.id })"
                         class="h-full w-full cursor-zoom-in object-cover transition hover:scale-105"
                         @click="$emit('preview', img.url)" />
                </div>
            </div>
            <p v-else class="py-4 text-center text-sm text-muted-foreground">
                {{ $t('trip.images.empty') }}
            </p>
        </section>
    </div>
</template>