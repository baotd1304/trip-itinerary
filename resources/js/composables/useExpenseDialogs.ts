import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { adminExpenseRoutes } from '@/lib/expense-routes-admin';
import type { AppExpense, ExpenseActionType, ExpenseDialogMode } from '@/types/expense';

const RELOAD_ONLY = ['expenses', 'expensesMeta', 'stats', 'filters'];

export function useExpenseDialogs() {
    const mainOpen = ref(false);
    const mode = ref<ExpenseDialogMode>('view');
    const activeExpense = ref<AppExpense | null>(null);
    const deleteOpen = ref(false);
    const target = ref<AppExpense | null>(null);
    const processingId = ref<number | null>(null);

    const openCreate = () => {
        activeExpense.value = null;
        mode.value = 'create';
        mainOpen.value = true;
    };

    const toggleActive = (expense: AppExpense) => {
        if (processingId.value !== null) {
            return;
        }

        processingId.value = expense.id;
        router.patch(adminExpenseRoutes.toggleActive(expense.id).url, {}, {
            preserveScroll: true,
            preserveState: true,
            only: RELOAD_ONLY,
            onFinish: () => {
                processingId.value = null;
            },
        });
    };

    const handleAction = ({ type, expense }: { type: ExpenseActionType; expense: AppExpense }) => {
        switch (type) {
            case 'view':
            case 'edit':
                activeExpense.value = expense;
                mode.value = type;
                mainOpen.value = true;
                break;
            case 'delete':
                target.value = expense;
                deleteOpen.value = true;
                break;
            case 'toggleActive':
                toggleActive(expense);
                break;
        }
    };

    return {
        mainOpen,
        mode,
        activeExpense,
        deleteOpen,
        target,
        processingId,
        openCreate,
        handleAction,
    };
}
