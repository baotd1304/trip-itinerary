<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import type { Advisor, Car, Driver, TripFormModel } from '@/types/trip';

defineProps<{
    cars: Car[];
    advisors: Advisor[];
    drivers: Driver[];
    isDriver: boolean;
    authUser?: { id: number; name: string } | null;
    errors?: Record<string, string>;
}>();

const model = defineModel<TripFormModel>({ required: true });

const distance = computed(() =>
    Math.max(0, (Number(model.value.odo_end) || 0) - (Number(model.value.odo_start) || 0)));
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="advisor_id">{{ $t('trip.form.advisor') }}</Label>
                <select id="advisor_id" v-model="model.advisor_id" name="advisor_id"
                        class="w-full rounded border p-2">
                    <option value="" disabled>{{ $t('trip.form.selectAdvisor') }}</option>
                    <option v-for="a in advisors" :key="a.id" :value="a.id">{{ a.id }} - {{ a.name }}</option>
                </select>
                <InputError :message="errors?.advisor_id" />
            </div>

            <div class="grid gap-2">
                <Label for="driver_id">{{ $t('trip.form.driver') }}</Label>
                <div v-if="isDriver" class="flex items-center rounded border bg-muted p-2 text-sm">
                    {{ authUser?.id }} - {{ authUser?.name }}
                    <input type="hidden" name="driver_id" :value="authUser?.id" />
                </div>
                <select v-else id="driver_id" v-model="model.driver_id" name="driver_id"
                        class="w-full rounded border p-2">
                    <option value="" disabled>{{ $t('trip.form.selectDriver') }}</option>
                    <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.id }} - {{ d.name }}</option>
                </select>
                <InputError :message="errors?.driver_id" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="f-day">{{ $t('trip.form.day') }}</Label>
                <Input id="f-day" v-model="model.day" type="date" name="day" required />
                <InputError :message="errors?.day" />
            </div>
            <div class="grid gap-2">
                <Label for="f-car">{{ $t('trip.form.car') }}</Label>
                <select id="f-car" v-model="model.car_id" name="car_id" class="w-full rounded border p-2">
                    <option value="" disabled>{{ $t('trip.form.selectCar') }}</option>
                    <option v-for="c in cars" :key="c.id" :value="c.id">{{ c.id }} - {{ c.license_plate }}</option>
                </select>
                <InputError :message="errors?.car_id" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="f-origin">{{ $t('trip.form.origin') }}</Label>
                <Input id="f-origin" v-model="model.origin" name="origin" required />
                <InputError :message="errors?.origin" />
            </div>
            <div class="grid gap-2">
                <Label for="f-destination">{{ $t('trip.form.destination') }}</Label>
                <Input id="f-destination" v-model="model.destination" name="destination" required />
                <InputError :message="errors?.destination" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="f-dep">{{ $t('trip.form.departureTime') }}</Label>
                <Input id="f-dep" v-model="model.departure_time" type="time" name="departure_time" required />
                <InputError :message="errors?.departure_time" />
            </div>
            <div class="grid gap-2">
                <Label for="f-arr">{{ $t('trip.form.arrivalTime') }}</Label>
                <Input id="f-arr" v-model="model.arrival_time" type="time" name="arrival_time" required />
                <InputError :message="errors?.arrival_time" />
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <Label for="f-odos">{{ $t('trip.form.odoStart') }}</Label>
                <Input id="f-odos" v-model.number="model.odo_start" type="number" min="0" name="odo_start" />
                <InputError :message="errors?.odo_start" />
            </div>
            <div class="grid gap-2">
                <Label for="f-odoe">{{ $t('trip.form.odoEnd') }}</Label>
                <Input id="f-odoe" v-model.number="model.odo_end" type="number"
                       :min="model.odo_start || 0" name="odo_end" />
                <InputError :message="errors?.odo_end" />
            </div>
            <div class="grid gap-2">
                <Label for="f-dist">{{ $t('trip.form.distanceAuto') }}</Label>
                <Input id="f-dist" :model-value="distance" readonly name="distance" class="bg-muted" />
                <InputError :message="errors?.distance" />
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <Label for="f-ot">{{ $t('trip.form.overtime') }}</Label>
                <Input id="f-ot" v-model.number="model.overtime" type="number" min="0" max="4" name="overtime" />
                <InputError :message="errors?.overtime" />
            </div>
            <div class="grid gap-2">
                <Label for="f-toll">{{ $t('trip.form.tollFee') }}</Label>
                <Input id="f-toll" v-model.number="model.toll_fee" type="number" min="0" name="toll_fee" />
                <InputError :message="errors?.toll_fee" />
            </div>
            <div class="grid gap-2">
                <Label for="f-air">{{ $t('trip.form.airportFee') }}</Label>
                <Input id="f-air" v-model.number="model.airport_fee" type="number" min="0" name="airport_fee" />
                <InputError :message="errors?.airport_fee" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2 rounded-md border p-3">
                <div class="flex items-center gap-2">
                    <Checkbox id="f-overnight" :model-value="model.is_overnight"
                              @update:model-value="(v: any) => (model.is_overnight = v === true)" />
                    <Label for="f-overnight" class="cursor-pointer">{{ $t('trip.form.overnight') }}</Label>
                </div>
                <input type="hidden" name="is_overnight" :value="model.is_overnight ? 1 : 0" />
                <InputError :message="errors?.is_overnight" />
            </div>

            <div class="grid gap-2 rounded-md border p-3">
                <div class="flex items-center gap-2">
                    <Checkbox id="f-holiday" :model-value="model.is_holiday"
                              @update:model-value="(v: any) => (model.is_holiday = v === true)" />
                    <Label for="f-holiday" class="cursor-pointer">{{ $t('trip.form.holiday') }}</Label>
                </div>
                <input type="hidden" name="is_holiday" :value="model.is_holiday ? 1 : 0" />
                <InputError :message="errors?.is_holiday" />
            </div>
        </div>
    </div>
</template>