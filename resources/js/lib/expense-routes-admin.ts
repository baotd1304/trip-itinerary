import {
    bulk,
    destroy,
    index,
    store,
    toggleActive,
    update,
} from '@/actions/App/Http/Controllers/Admin/ExpenseController';

export const adminExpenseRoutes = {
    index,
    store,
    update: (id: number) => update(id),
    destroy: (id: number) => destroy(id),
    toggleActive: (id: number) => toggleActive(id),
    bulk,
};
