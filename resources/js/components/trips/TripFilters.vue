<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Loader2, Search } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { statusKey } from '@/lib/trip-status';
import type { TripStatus } from '@/types/trip';
import TripSurchargeFilter from './TripSurchargeFilter.vue';
import type { TriState } from './TripSurchargeFilter.vue';

const props = withDefaults(
    defineProps<{
        url: string;
        initial?: {
            search?: string | null;
            status?: TripStatus | null;
            pending_reopen?: '1' | null;
            from?: string | null;
            to?: string | null;
            is_overnight?: '1' | '0' | null;
            is_holiday?: '1' | '0' | null;
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
    if (v === null || v === undefined || v === '') {
        return '';
    }

    if (v === 1 || v === '1' || v === true) {
        return '1';
    }

    if (v === 0 || v === '0' || v === false) {
        return '0';
    }

    return '';
};

const search = ref<string>(props.initial?.search ?? '');
const status = ref<TripStatus | ''>(
    (props.initial?.status as TripStatus) ?? '',
);
const pendingReopen = ref<TriState>(toTri(props.initial?.pending_reopen));
const from = ref<string>(props.initial?.from ?? '');
const to = ref<string>(props.initial?.to ?? '');
const isOvernight = ref<TriState>(toTri(props.initial?.is_overnight));
const isHoliday = ref<TriState>(toTri(props.initial?.is_holiday));
const loading = ref(false);

/** Chặn watcher props.initial ghi đè ngay sau khi user vừa đổi filter */
let selfUpdating = false;

const apply = () => {
    selfUpdating = true;

    router.get(
        props.url,
        {
            search: search.value || undefined,
            status: status.value || undefined,
            pending_reopen: pendingReopen.value === '1' ? '1' : undefined,
            from: from.value || undefined,
            to: to.value || undefined,
            // Không dùng `|| undefined`: chuỗi '0' phải được gửi đi
            is_overnight:
                isOvernight.value !== '' ? isOvernight.value : undefined,
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
};

// Đồng bộ khi server đẩy filters mới (Back/Forward)
watch(
    () => props.initial,
    (v) => {
        if (selfUpdating) {
            return;
        }

        search.value = v?.search ?? '';
        status.value = (v?.status as TripStatus) ?? '';
        pendingReopen.value = toTri(v?.pending_reopen);
        from.value = v?.from ?? '';
        to.value = v?.to ?? '';
        isOvernight.value = toTri(v?.is_overnight);
        isHoliday.value = toTri(v?.is_holiday);
    },
    { deep: true },
);

/** Tổng số filter đang bật (hiển thị trên nút Tìm kiếm) */
const activeCount = computed(
    () =>
        (search.value.trim() ? 1 : 0) +
        (status.value ? 1 : 0) +
        (pendingReopen.value === '1' ? 1 : 0) +
        (from.value ? 1 : 0) +
        (to.value ? 1 : 0) +
        (isOvernight.value !== '' ? 1 : 0) +
        (isHoliday.value !== '' ? 1 : 0),
);

const reset = () => {
    search.value = '';
    status.value = '';
    pendingReopen.value = '';
    from.value = '';
    to.value = '';
    isOvernight.value = '';
    isHoliday.value = '';
    apply();
};

const applyStatsFilter = (filter: TripStatus | 'pendingReopen') => {
    if (filter === 'pendingReopen') {
        pendingReopen.value = pendingReopen.value === '1' ? '' : '1';
        status.value = '';

        apply();

        return;
    }

    status.value = status.value === filter ? '' : filter;
    pendingReopen.value = '';
    apply();
};

defineExpose({ applyStatsFilter });
</script>

<template>
    <form
        class="mb-4 grid min-w-0 gap-3 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-6"
        @submit.prevent="apply"
    >
        <!-- Tìm kiếm -->
        <div class="grid gap-1.5 sm:col-span-2">
            <Label for="trip-search" class="text-xs text-muted-foreground">
                {{ $t('common.search') }}
            </Label>

            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="trip-search"
                    v-model="search"
                    type="search"
                    class="pr-8 pl-8"
                    :placeholder="$t('trip.filters.searchPlaceholder')"
                />
                <Loader2
                    v-if="loading"
                    class="absolute top-1/2 right-2.5 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
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
            <Input
                id="trip-from"
                v-model="from"
                type="date"
                :max="to || undefined"
            />
        </div>

        <!-- Đến ngày + Reset -->
        <div class="grid min-w-0 gap-1.5">
            <Label for="trip-to" class="text-xs text-muted-foreground">
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
            </div>
        </div>
        <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-6">
            <Button type="button" variant="outline" size="sm" @click="reset">
                {{ $t('trip.filters.reset') }}
            </Button>
            <Button type="submit" size="sm" class="relative">
                {{ $t('common.search') }}
                <Badge
                    v-if="activeCount"
                    variant="destructive"
                    class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                    :aria-label="
                        $t('trip.filters.activeCount', { count: activeCount })
                    "
                    :title="
                        $t('trip.filters.activeCount', { count: activeCount })
                    "
                >
                    {{ activeCount }}
                </Badge>
            </Button>
        </div>
    </form>
</template>
