<script setup lang="ts">
import {
    EyeIcon,
    Loader2Icon,
    LockIcon,
    Trash2Icon,
    UnlockIcon,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { AppExpense, ExpenseActionType } from '@/types/expense';
import { ADMIN_EXPENSE_TABLE_ACTIONS } from '@/types/expense-table';

const props = withDefaults(defineProps<{
    expense: AppExpense;
    only?: readonly ExpenseActionType[];
    processing?: boolean;
}>(), {
    only: () => ADMIN_EXPENSE_TABLE_ACTIONS,
    processing: false,
});

const emit = defineEmits<{
    (event: 'action', payload: { type: ExpenseActionType; expense: AppExpense }): void;
}>();

const isActive = () => props.expense.is_active === true || props.expense.is_active === 1;
const show = (type: ExpenseActionType) => props.only.includes(type);
const fire = (type: ExpenseActionType) => emit('action', { type, expense: props.expense });
</script>

<template>
    <div class="flex items-center justify-center gap-0">
        <Tooltip v-if="show('view')">
            <TooltipTrigger as-child>
                <Button type="button" variant="ghost" size="icon" class="h-7 w-7" :aria-label="$t('common.view')" @click.stop="fire('view')">
                    <EyeIcon class="h-3.5 w-3.5" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.view') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('toggleActive') && expense.can?.toggleActive">
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7"
                    :disabled="processing"
                    :aria-label="isActive() ? $t('expense.actions.deactivate') : $t('expense.actions.activate')"
                    @click.stop="fire('toggleActive')"
                >
                    <Loader2Icon v-if="processing" class="h-3.5 w-3.5 animate-spin" />
                    <LockIcon v-else-if="isActive()" class="h-3.5 w-3.5 text-amber-600" />
                    <UnlockIcon v-else class="h-3.5 w-3.5 text-emerald-600" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {{ isActive() ? $t('expense.actions.deactivate') : $t('expense.actions.activate') }}
            </TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('delete') && expense.can?.delete">
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950"
                    :aria-label="$t('common.delete')"
                    @click.stop="fire('delete')"
                >
                    <Trash2Icon class="h-3.5 w-3.5" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.delete') }}</TooltipContent>
        </Tooltip>
    </div>
</template>
