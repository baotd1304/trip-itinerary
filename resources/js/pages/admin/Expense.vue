<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ExpenseBulkDialog from '@/components/expenses/ExpenseBulkDialog.vue';
import ExpenseDeleteDialog from '@/components/expenses/ExpenseDeleteDialog.vue';
import ExpenseFilters from '@/components/expenses/ExpenseFilters.vue';
import ExpenseFormDialog from '@/components/expenses/ExpenseFormDialog.vue';
import ExpenseTable from '@/components/expenses/ExpenseTable.vue';
import TripPagination from '@/components/trips/TripPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useExpenseDialogs } from '@/composables/useExpenseDialogs';
import { usePagination } from '@/composables/usePagination';
import type { PaginationMeta } from '@/composables/usePagination';
import { toArray } from '@/lib/array';
import { adminExpenseRoutes } from '@/lib/expense-routes-admin';
import admin from '@/routes/admin';
import type {
    AppExpense,
    ExpenseBulkAction,
    ExpenseCreator,
    ExpenseDialogMode,
} from '@/types/expense';
import type { PaginationLink } from '@/types/trip';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'adminLayout.breadcrumbs.expenses',
                href: admin.expenses.index(),
            },
        ],
    },
});

interface ExpenseFiltersState {
    search?: string | null;
    is_active?: '1' | '0' | null;
    created_by?: number | null;
    from?: string | null;
    to?: string | null;
    sort?: string | null;
    dir?: 'asc' | 'desc' | null;
}

const props = defineProps<{
    expenses: { data?: AppExpense[]; links?: PaginationLink[]; meta?: { links?: PaginationLink[] } } | AppExpense[];
    expensesMeta?: PaginationMeta;
    creators?: ExpenseCreator[];
    stats?: {
        total?: number;
        active?: number;
        inactive?: number;
    };
    filters?: ExpenseFiltersState;
    can?: {
        create?: boolean;
        toggleActive?: boolean;
        delete?: boolean;
    };
}>();

const expenseFilters = ref<InstanceType<typeof ExpenseFilters> | null>(null);
const items = computed(() => toArray<AppExpense>(props.expenses));
const creators = computed(() => props.creators ?? []);
const hasStats = computed(
    () => !!props.stats && Object.values(props.stats).some((value) => (value ?? 0) > 0),
);
const links = computed<PaginationLink[]>(() =>
    Array.isArray(props.expenses) ? [] : props.expenses.links ?? props.expenses.meta?.links ?? [],
);
const { total: totalCount, rangeFrom, rangeTo } = usePagination(
    computed(() => props.expenses),
    computed(() => props.expensesMeta),
    computed(() => items.value.length),
);

const {
    mainOpen,
    mode,
    activeExpense,
    deleteOpen,
    target,
    processingId,
    openCreate,
    handleAction,
} = useExpenseDialogs();

const selected = ref<number[]>([]);
const bulkOpen = ref(false);
const bulkAction = ref<ExpenseBulkAction>('activate');
const bulkProcessing = ref(false);

const openBulk = (action: ExpenseBulkAction) => {
    if (!selected.value.length || bulkProcessing.value) {
        return;
    }

    bulkAction.value = action;
    bulkOpen.value = true;
};

const runBulk = () => {
    if (bulkProcessing.value || !selected.value.length) {
        return;
    }

    bulkProcessing.value = true;
    router.post(
        adminExpenseRoutes.bulk().url,
        {
            action: bulkAction.value,
            expense_ids: selected.value,
        },
        {
            preserveScroll: true,
            only: ['expenses', 'expensesMeta', 'stats', 'filters'],
            onSuccess: () => {
                selected.value = [];
                bulkOpen.value = false;
            },
            onFinish: () => {
                bulkProcessing.value = false;
            },
        },
    );
};

const setMode = (nextMode: ExpenseDialogMode) => {
    mode.value = nextMode;
};
</script>

<template>
    <Head :title="$t('expense.admin.pageTitle')" />

    <div class="space-y-4 p-4">
        <Card class="overflow-hidden">
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <CardTitle>{{ $t('expense.admin.listTitle') }}</CardTitle>

                        <div v-if="hasStats" class="flex flex-wrap items-center gap-1.5">
                            <Badge variant="secondary">
                                {{ $t('expense.stats.total') }}: {{ props.stats?.total }}
                            </Badge>

                            <Badge
                                v-if="props.stats?.active"
                                as="button"
                                type="button"
                                :aria-pressed="props.filters?.is_active === '1'"
                                class="cursor-pointer bg-emerald-600 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                                @click="expenseFilters?.applyStatsFilter('active')"
                            >
                                {{ $t('expense.status.active') }}: {{ props.stats.active }}
                            </Badge>

                            <Badge
                                v-if="props.stats?.inactive"
                                as="button"
                                type="button"
                                :aria-pressed="props.filters?.is_active === '0'"
                                class="cursor-pointer bg-gray-500 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                                @click="expenseFilters?.applyStatsFilter('inactive')"
                            >
                                {{ $t('expense.status.inactive') }}: {{ props.stats.inactive }}
                            </Badge>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button v-if="props.can?.create" size="sm" @click="openCreate">
                            {{ $t('expense.create') }}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <ExpenseFilters
                    ref="expenseFilters"
                    :url="adminExpenseRoutes.index().url"
                    :creators="creators"
                    :initial="props.filters"
                />

                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-muted-foreground">
                        <template v-if="totalCount">
                            {{ $t('expense.filters.resultRange', { from: rangeFrom, to: rangeTo, total: totalCount }) }}
                        </template>
                        <template v-else>{{ $t('expense.filters.resultEmpty') }}</template>
                    </p>
                    <div v-if="selected.length" class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-muted-foreground">
                            {{ $t('common.selectedCount', { count: selected.length }) }}
                        </span>
                        <Button
                            v-if="props.can?.toggleActive"
                            size="xs"
                            variant="outline"
                            :disabled="bulkProcessing"
                            @click="openBulk('activate')"
                        >
                            {{ $t('expense.actions.activate') }}
                        </Button>
                        <Button
                            v-if="props.can?.toggleActive"
                            size="xs"
                            variant="outline"
                            :disabled="bulkProcessing"
                            @click="openBulk('deactivate')"
                        >
                            {{ $t('expense.actions.deactivate') }}
                        </Button>
                        <Button
                            v-if="props.can?.delete"
                            size="xs"
                            variant="destructive"
                            :disabled="bulkProcessing"
                            @click="openBulk('delete')"
                        >
                            {{ $t('common.delete') }}
                        </Button>
                    </div>
                </div>

                <ExpenseTable
                    v-model:selected="selected"
                    :expenses="items"
                    :processing-id="processingId"
                    :bulk-processing="bulkProcessing"
                    @action="handleAction"
                />

                <TripPagination :links="links" :fallback-url="adminExpenseRoutes.index().url" />
            </CardContent>
        </Card>

        <ExpenseFormDialog
            v-model:open="mainOpen"
            :mode="mode"
            :expense="activeExpense"
            @switch-mode="setMode"
        />
        <ExpenseDeleteDialog v-model:open="deleteOpen" :expense="target" />
        <ExpenseBulkDialog
            v-model:open="bulkOpen"
            :action="bulkAction"
            :count="selected.length"
            :processing="bulkProcessing"
            @confirm="runBulk"
        />
    </div>
</template>
