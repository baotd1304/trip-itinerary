import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { adminCarRoutes } from '@/lib/car-routes-admin';
import type { AppCar, CarActionType, CarDialogMode } from '@/types/car';

const RELOAD_ONLY = ['cars', 'carsMeta', 'stats', 'brands', 'filters'];

export function useCarDialogs() {
    const mainOpen = ref(false);
    const mode = ref<CarDialogMode>('view');
    const activeCar = ref<AppCar | null>(null);
    const deleteOpen = ref(false);
    const target = ref<AppCar | null>(null);
    const processingId = ref<number | null>(null);

    const openCreate = () => {
        activeCar.value = null;
        mode.value = 'create';
        mainOpen.value = true;
    };

    const toggleActive = (car: AppCar) => {
        if (processingId.value !== null) {
            return;
        }

        processingId.value = car.id;
        router.patch(adminCarRoutes.toggleActive(car.id).url, {}, {
            preserveScroll: true,
            preserveState: true,
            only: RELOAD_ONLY,
            onFinish: () => {
                processingId.value = null;
            },
        });
    };

    const handleAction = ({ type, car }: { type: CarActionType; car: AppCar }) => {
        switch (type) {
            case 'view':
            case 'edit':
                activeCar.value = car;
                mode.value = type;
                mainOpen.value = true;
                break;
            case 'delete':
                target.value = car;
                deleteOpen.value = true;
                break;
            case 'toggleActive':
                toggleActive(car);
                break;
        }
    };

    return {
        mainOpen,
        mode,
        activeCar,
        deleteOpen,
        target,
        processingId,
        openCreate,
        handleAction,
    };
}
