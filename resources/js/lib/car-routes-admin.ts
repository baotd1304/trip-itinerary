import {
    bulk,
    destroy,
    index,
    store,
    toggleActive,
    update,
} from '@/actions/App/Http/Controllers/Admin/CarController';

export const adminCarRoutes = {
    index,
    store,
    update: (id: number) => update(id),
    destroy: (id: number) => destroy(id),
    toggleActive: (id: number) => toggleActive(id),
    bulk,
};
