import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { adminUserRoutes } from '@/lib/user-routes-admin';
import type { AppUser, UserActionType, UserDialogMode } from '@/types/user';

const RELOAD_ONLY = ['users', 'usersMeta', 'stats'];

export function useUserDialogs() {
    const mainOpen = ref(false);
    const mode = ref<UserDialogMode>('view');
    const activeUser = ref<AppUser | null>(null);

    const deleteOpen = ref(false);
    const target = ref<AppUser | null>(null);

    const processingId = ref<number | null>(null);

    const openCreate = () => {
        activeUser.value = null;
        mode.value = 'create';
        mainOpen.value = true;
    };

    const toggleActive = (user: AppUser) => {
        if (processingId.value) {
return;
}

        processingId.value = user.id;

        router.patch(adminUserRoutes.toggleActive(user.id).url, {}, {
            preserveScroll: true,
            preserveState: true,
            only: RELOAD_ONLY,
            onFinish: () => {
 processingId.value = null; 
},
        });
    };

    const verifyEmail = (user: AppUser) => {
        if (processingId.value) {
return;
}

        processingId.value = user.id;

        router.patch(adminUserRoutes.verifyEmail(user.id).url, {}, {
            preserveScroll: true,
            preserveState: true,
            only: RELOAD_ONLY,
            onFinish: () => {
 processingId.value = null; 
},
        });
    };

    const handleAction = ({ type, user }: { type: UserActionType; user: AppUser }) => {
        switch (type) {
            case 'view':
                activeUser.value = user;
                mode.value = 'view';
                mainOpen.value = true;
                break;

            case 'edit':
                activeUser.value = user;
                mode.value = 'edit';
                mainOpen.value = true;
                break;

            case 'assignRole':
                activeUser.value = user;
                mode.value = 'edit';
                mainOpen.value = true;
                break;

            case 'delete':
                target.value = user;
                deleteOpen.value = true;
                break;

            case 'toggleActive':
                toggleActive(user);
                break;

            case 'verifyEmail':
                verifyEmail(user);
                break;
        }
    };

    return {
        mainOpen, mode, activeUser,
        deleteOpen, target,
        processingId,
        openCreate, handleAction,
    };
}