<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Loader2, Search, X } from 'lucide-vue-next';
import TripSurchargeFilter, { type TriState } from './TripSurchargeFilter.vue';
import { statusKey } from '@/lib/trip-status';
import type { TripStatus } from '@/types/trip';

const props = withDefaults(
    defineProps<{
        url: string;
        initial?: {
            search?: string | null;
            status?: TripStatus | null;
            from?: string | null;
            to?: string | null;
            is_overnight?: string | number | null;
            is_holiday?: string | number | null;
        };
        /** BẮT BUỘC có 'filters', nếu không select sẽ tự reset */
        only?: string[];
    }>(),
    {
        initial: () => ({}),
        only: () => ['trips', 'tripsMeta', 'filters'],
    },
);

const STATUSES: TripStatus[] = ['pending', 'editing', 'confirmed', 'rejected'];

/** Chuẩn hoá mọi kiểu về '' | '1' | '0' */
const toTri = (v: unknown): TriState => {
    if (v === null || v === undefined || v === '') return '';
    if (v === 1 || v === '1' || v === true) return '1';
    if (v === 0 || v === '0' || v === false) return '0';
    return '';
};

const search = ref<string>(props.initial?.search ?? '');
const status = ref<TripStatus | ''>((props.initial?.status as TripStatus) ?? '');
const from = ref<string>(props.initial?.from ?? '');
const to = ref<string>(props.initial?.to ?? '');
const isOvernight = ref<TriState>(toTri(props.initial?.is_overnight));
const isHoliday = ref<TriState>(toTri(props.initial?.is_holiday));
const loading = ref(false);

let timer: ReturnType<typeof setTimeout> | undefined;
/** Chặn watcher props.initial ghi đè ngay sau khi user vừa đổi filter */
let selfUpdating = false;

const apply = (delay = 0) => {
    clearTimeout(timer);

    timer = setTimeout(() => {
        selfUpdating = true;

        router.get(
            props.url,
            {
                search: search.value || undefined,
                status: status.value || undefined,
                from: from.value || undefined,
                to: to.value || undefined,
                // Không dùng `|| undefined`: chuỗi '0' phải được gửi đi
                is_overnight: isOvernight.value !== '' ? isOvernight.value : undefined,
                is_holiday: isHoliday.value !== '' ? isHoliday.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: props.only,
                onStart: () => (loading.value = true),
                onFinish: () => {
                    loading.value = false;
                    selfUpdating = false;
                },
            },
        );
    }, delay);
};

// Gõ phím: debounce 350ms
watch(search, () => apply(350));
// Select và date: lọc ngay khi giá trị thay đổi
watch([status, from, to, isOvernight, isHoliday], () => apply(0));

// Đồng bộ khi server đẩy filters mới (Back/Forward)
watch(
    () => props.initial,
    (v) => {
        if (selfUpdating) return;
        search.value = v?.search ?? '';
        status.value = (v?.status as TripStatus) ?? '';
        from.value = v?.from ?? '';
        to.value = v?.to ?? '';
        isOvernight.value = toTri(v?.is_overnight);
        isHoliday.value = toTri(v?.is_holiday);
    },
    { deep: true },
);

/** Tổng số filter đang bật (hiện trên badge cạnh nút Xoá lọc) */
const activeCount = computed(
    () =>
        (search.value ? 1 : 0) +
        (status.value ? 1 : 0) +
        (from.value ? 1 : 0) +
        (to.value ? 1 : 0) +
        (isOvernight.value !== '' ? 1 : 0) +
        (isHoliday.value !== '' ? 1 : 0),
);

const reset = () => {
    search.value = '';
    status.value = '';
    from.value = '';
    to.value = '';
    isOvernight.value = '';
    isHoliday.value = '';
    apply(0);
};

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="mb-4 grid min-w-0 gap-3 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-6">
        <!-- Tìm kiếm -->
        <div class="grid gap-1.5 sm:col-span-2">
            <Label for="trip-search" class="text-xs text-muted-foreground">
                {{ $t('common.search') }}
            </Label>

            <div class="relative">
                <Search
                    class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="trip-search"
                    v-model="search"
                    type="search"
                    class="pl-8 pr-8"
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

        <!-- NHÓM PHỤ PHÍ (Nghỉ đêm + Ngày lễ) -->
        <TripSurchargeFilter
            v-model:overnight="isOvernight"
            v-model:holiday="isHoliday"
        />

        <!-- Từ ngày -->
        <div class="grid gap-1.5">
            <Label for="trip-from" class="text-xs text-muted-foreground">
                {{ $t('trip.filters.fromDate') }}
            </Label>
            <Input id="trip-from" v-model="from" type="date" :max="to || undefined" />
        </div>

        <!-- Đến ngày + Reset -->
        <div class="grid min-w-0 gap-1.5">
            <Label
                for="trip-to"
                class="text-xs text-muted-foreground"
            >
                {{ $t('trip.filters.toDate') }}
            </Label>

            <div class="flex min-w-0 items-center gap-2">
                <Input
                    id="trip-to"
                    v-model="to"
                    type="date"
                    :min="from || undefined"
                    class="min-w-0 flex-1"
                />

                <Button
                    v-if="activeCount"
                    type="button"
                    variant="outline"
                    size="icon"
                    class="relative h-9 w-9 shrink-0"
                    :title="$t('trip.filters.reset')"
                    :aria-label="$t('trip.filters.reset')"
                    @click="reset"
                >
                    <X class="h-4 w-4" />

                    <Badge
                        class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                        variant="destructive"
                    >
                        {{ activeCount }}
                    </Badge>
                </Button>
            </div>
        </div>
    </div>
</template>