<script setup lang="ts">
import { CircleCheckBigIcon, CircleX } from '@lucide/vue';
import TripActions from './TripActions.vue';
import TripStatusCell from './TripStatusCell.vue';
import { useFormat } from '@/composables/useFormat';
import type { Trip, TripActionPayload, TripActionType } from '@/types/trip';

defineProps<{ trips: Trip[]; confirmingId: number | null }>();
defineEmits<{ action: [TripActionPayload] }>();

const { formatDate, formatMoney } = useFormat();

const HEADER_KEYS = [
    'id', 'advisor', 'driver', 'origin', 'destination', 'day',
    'distance', 'status', 'overnight', 'holiday', 'totalFee', 'actions',
];
const TABLE_ACTIONS: TripActionType[] = ['view'];
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800">
                    <th v-for="key in HEADER_KEYS" :key="key"
                        class="border border-gray-300 px-2 py-2 text-center dark:border-gray-700">
                        {{ $t(`trip.table.${key}`) }}
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr v-if="!trips.length">
                    <td :colspan="HEADER_KEYS.length" class="px-4 py-8 text-center text-muted-foreground">
                        {{ $t('common.noData') }}
                    </td>
                </tr>

                <tr v-for="trip in trips" :key="trip.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.id }}</td>
                    <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.advisor?.name }}</td>
                    <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.driver?.name }}</td>
                    <td class="max-w-[150px] truncate border px-2 py-2 text-center align-top dark:border-gray-700"
                        :title="trip.origin">{{ trip.origin }}</td>
                    <td class="max-w-[150px] truncate border px-2 py-2 text-center align-top dark:border-gray-700"
                        :title="trip.destination">{{ trip.destination }}</td>
                    <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ formatDate(trip.day) }}</td>
                    <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.distance }}</td>

                    <td class="border px-2 py-2 text-center align-top dark:border-gray-700">
                        <TripStatusCell :trip="trip" />
                    </td>

                    <td class="border px-2 py-2 align-top dark:border-gray-700">
                        <div class="flex items-center justify-center">
                            <CircleCheckBigIcon v-if="trip.trip_expense?.is_overnight" class="text-green-500" />
                            <CircleX v-else class="text-gray-400" />
                        </div>
                    </td>
                    <td class="border px-2 py-2 align-top dark:border-gray-700">
                        <div class="flex items-center justify-center">
                            <CircleCheckBigIcon v-if="trip.trip_expense?.is_holiday" class="text-green-500" />
                            <CircleX v-else class="text-gray-400" />
                        </div>
                    </td>

                    <td class="border px-2 py-2 text-right align-top dark:border-gray-700">
                        {{ formatMoney(trip.total_fee) }}
                    </td>

                    <td class="border px-2 py-2 align-top dark:border-gray-700">
                        <TripActions
                            :trip="trip"
                            variant="table"
                            :only="TABLE_ACTIONS"
                            :confirming="confirmingId === trip.id"
                            @action="$emit('action', $event)" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>