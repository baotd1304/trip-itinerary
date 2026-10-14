import {
    destroyPermission,
    destroyRole,
    storePermission,
    storeRole,
    updatePermission,
    updateRole,
} from '@/actions/App/Http/Controllers/Admin/AccessControlController';
import {
    bulk,
    destroy,
    index,
    store,
    toggleActive,
    update,
    verifyEmail,
} from '@/actions/App/Http/Controllers/Admin/UserController';

export const adminUserRoutes = {
    index,
    store,
    update: (id: number) => update(id),
    destroy: (id: number) => destroy(id),
    bulk,
    toggleActive: (id: number) => toggleActive(id),
    verifyEmail: (id: number) => verifyEmail(id),
    storeRole,
    updateRole: (id: number) => updateRole(id),
    destroyRole: (id: number) => destroyRole(id),
    storePermission,
    updatePermission: (id: number) => updatePermission(id),
    destroyPermission: (id: number) => destroyPermission(id),
};
