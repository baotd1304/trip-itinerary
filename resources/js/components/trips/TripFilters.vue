<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Loader2, Search, X } from 'lucide-vue-next';
import { statusKey } from '@/lib/trip-status';
import type { TripStatus } from '@/types/trip';

const props = withDefaults(
    defineProps<{
        /** URL endpoint index, vd: trips.index().url */
        url: string;
        /** Giá trị lọc hiện tại do server trả về */
        initial?: {
            search?: string | null;
            status?: TripStatus | '' | null;
            from?: string | null;
            to?: string | null;
            is_overnight?: number;
            is_holiday?: number;
        };
        /** Partial reload: chỉ nạp lại các prop cần thiết */
        only?: string[];
    }>(),
    {
        initial: () => ({}),
        is_overnight: () => [],
        is_holiday: () => [],
        // mặc định phải có tripsMeta + filters, nếu không tổng số sẽ không đổi
        only: () => ['trips', 'tripsMeta', 'filters'],
    },
);

const STATUSES: TripStatus[] = ['pending', 'editing', 'confirmed', 'rejected'];

const search = ref<string>(props.initial?.search ?? '');
const status = ref<TripStatus | ''>((props.initial?.status as TripStatus) ?? '');
const from = ref<string>(props.initial?.from ?? '');
const to = ref<string>(props.initial?.to ?? '');
const loading = ref(false);

let timer: ReturnType<typeof setTimeout> | undefined;

const apply = (delay = 0) => {
    clearTimeout(timer);

    timer = setTimeout(() => {
        router.get(
            props.url,
            {
                search: search.value || undefined,
                status: status.value || undefined,
                from: from.value || undefined,
                to: to.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: props.only,
                onStart: () => (loading.value = true),
                onFinish: () => (loading.value = false),
            },
        );
    }, delay);
};

// Gõ phím: debounce 350ms — Select/Date: áp dụng ngay
watch(search, () => apply(350));
watch([status, from, to], () => apply(0));

// Đồng bộ ngược khi server trả filters khác (vd: bấm Back trình duyệt)
watch(
    () => props.initial,
    (v) => {
        search.value = v?.search ?? '';
        status.value = (v?.status as TripStatus) ?? '';
        from.value = v?.from ?? '';
        to.value = v?.to ?? '';
    },
    { deep: true },
);

const hasFilter = computed(
    () => !!(search.value || status.value || from.value || to.value),
);

const reset = () => {
    search.value = '';
    status.value = '';
    from.value = '';
    to.value = '';
    apply(0);
};

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="mb-4 grid gap-3 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-5">
        <!-- Tìm kiếm -->
        <div class="grid gap-1.5 sm:col-span-2">
            <Label for="trip-search" class="text-xs text-muted-foreground">
                {{ $t('common.search') }}
            </Label>

            <div class="relative">
                <Search class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />

                <Input
                    id="trip-search"
                    v-model="search"
                    type="search"
                    class="pl-8 pr-4"
                    :placeholder="$t('trip.filters.searchPlaceholder')"
                />

                <Loader2
                    v-if="loading"
                    class="absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
                />
            </div>
        </div>

        <!-- Trạng thái -->
        <div class="grid gap-1.5">
            <Label for="trip-status" class="text-xs text-muted-foreground">
                {{ $t('trip.filters.status') }}
            </Label>

            <select
                id="trip-status"
                v-model="status"
                class="h-9 w-full rounded-md border bg-background px-2 text-sm"
            >
                <option value="">{{ $t('trip.filters.allStatus') }}</option>
                <option v-for="s in STATUSES" :key="s" :value="s">
                    {{ $t(statusKey(s)) }}
                </option>
            </select>
        </div>
        <!-- Overnight -->
        <div class="grid gap-1.5">
            <Label for="overnight" class="text-xs text-muted-foreground">
                {{ $t('trip.filters.status') }}
            </Label>

            <select
                id="overnight"
                v-model="status"
                class="h-9 w-full rounded-md border bg-background px-2 text-sm"
            >
                <option value="">{{ $t('trip.filters.allStatus') }}</option>
                <option v-for="s in STATUSES" :key="s" :value="s">
                    {{ $t(statusKey(s)) }}
                </option>
            </select>
        </div>
        <!-- Holiday -->
        <div class="grid gap-1.5">
            <Label for="holiday" class="text-xs text-muted-foreground">
                {{ $t('trip.filters.status') }}
            </Label>

            <select
                id="holiday"
                v-model="status"
                class="h-9 w-full rounded-md border bg-background px-2 text-sm"
            >
                <option value="">{{ $t('trip.filters.allStatus') }}</option>
                <option v-for="s in STATUSES" :key="s" :value="s">
                    {{ $t(statusKey(s)) }}
                </option>
            </select>
        </div>

        <!-- Từ ngày -->
        <div class="grid gap-1.5">
            <Label for="trip-from" class="text-xs text-muted-foreground">
                {{ $t('trip.filters.fromDate') }}
            </Label>
            <Input id="trip-from" v-model="from" type="date" :max="to || undefined" />
        </div>

        <!-- Đến ngày + Reset -->
        <div class="grid gap-1.5">
            <Label for="trip-to" class="text-xs text-muted-foreground">
                {{ $t('trip.filters.toDate') }}
            </Label>

            <div class="flex gap-2">
                <Input id="trip-to" v-model="to" type="date" :min="from || undefined" />

                <Button
                    v-if="hasFilter"
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="shrink-0"
                    :title="$t('trip.filters.reset')"
                    @click="reset"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>