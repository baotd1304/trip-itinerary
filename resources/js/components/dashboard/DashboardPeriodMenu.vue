<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CalendarDays } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

type PeriodOption = {
    value: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        modelValue: string;
        monthOptions: PeriodOption[];
        yearOptions: PeriodOption[];
        label: string;
        monthsLabel: string;
        yearsLabel: string;
        yearOnly?: boolean;
    }>(),
    {
        yearOnly: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const open = ref(false);
const view = ref<'month' | 'year'>('month');

/** Chế độ suy ra từ giá trị đang chọn */
const resolvedView = computed<'month' | 'year'>(() => {
    if (props.yearOnly) return 'year';
    return props.yearOptions.some((o) => o.value === props.modelValue)
        ? 'year'
        : 'month';
});

/** Mỗi lần mở menu: reset về đúng nhóm đang được chọn */
watch(open, (isOpen) => {
    if (isOpen) view.value = resolvedView.value;
});

watch(resolvedView, (v) => {
    if (!open.value) view.value = v;
});

const options = computed(() =>
    view.value === 'year' ? props.yearOptions : props.monthOptions,
);

const currentLabel = computed(
    () =>
        [...props.monthOptions, ...props.yearOptions].find(
            (o) => o.value === props.modelValue,
        )?.label ?? props.label,
);

function select(value: string) {
    emit('update:modelValue', value);
    open.value = false;
}
</script>

<template>
    <DropdownMenu v-model:open="open">
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="shrink-0"
                :aria-label="`${label}: ${currentLabel}`"
                :title="`${label}: ${currentLabel}`"
            >
                <CalendarDays class="size-4" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            :side-offset="6"
            class="w-[min(92vw,20rem)] p-2 sm:w-80"
        >
            <!-- Thanh chuyển Tháng / Năm -->
            <div
                v-if="!yearOnly"
                class="mb-2 grid grid-cols-2 gap-1 rounded-md bg-muted p-1"
                role="tablist"
                :aria-label="label"
            >
                <button
                    v-for="tab in [
                        { key: 'month' as const, text: monthsLabel },
                        { key: 'year' as const, text: yearsLabel },
                    ]"
                    :key="tab.key"
                    type="button"
                    role="tab"
                    :aria-selected="view === tab.key"
                    :class="
                        cn(
                            'rounded-sm px-3 py-1.5 text-sm font-medium transition-colors',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            view === tab.key
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="view = tab.key"
                >
                    {{ tab.text }}
                </button>
            </div>

            <p
                v-else
                class="px-1 pb-2 text-sm font-medium text-muted-foreground"
            >
                {{ yearsLabel }}
            </p>

            <!-- Lưới lịch: chỉ hiển thị 1 nhóm tại 1 thời điểm -->
            <div
                :class="
                    cn(
                        'grid max-h-64 gap-1 overflow-y-auto overscroll-contain p-0.5',
                        view === 'month'
                            ? 'grid-cols-3'
                            : 'grid-cols-3 sm:grid-cols-4',
                    )
                "
                role="radiogroup"
                :aria-label="view === 'month' ? monthsLabel : yearsLabel"
            >
                <button
                    v-for="option in options"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="option.value === modelValue"
                    :class="
                        cn(
                            'flex h-10 items-center justify-center rounded-md px-2 text-sm font-medium tabular-nums transition-colors sm:h-9',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            option.value === modelValue
                                ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                : 'hover:bg-accent hover:text-accent-foreground',
                        )
                    "
                    @click="select(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>

            <p
                v-if="!options.length"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                —
            </p>
        </DropdownMenuContent>
    </DropdownMenu>
</template>