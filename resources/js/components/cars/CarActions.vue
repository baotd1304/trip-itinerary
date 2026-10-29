<script setup lang="ts">
import {
    EyeIcon,
    Loader2Icon,
    LockIcon,
    PencilIcon,
    Trash2Icon,
    UnlockIcon,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { ADMIN_CAR_TABLE_ACTIONS } from '@/lib/car-action-presets';
import type { AppCar, CarActionType } from '@/types/car';

const props = withDefaults(defineProps<{
    car: AppCar;
    only?: CarActionType[];
    processing?: boolean;
}>(), {
    only: () => ADMIN_CAR_TABLE_ACTIONS,
    processing: false,
});

const emit = defineEmits<{
    (event: 'action', payload: { type: CarActionType; car: AppCar }): void;
}>();

const isActive = () => props.car.is_active === true || props.car.is_active === 1;
const show = (type: CarActionType) => props.only.includes(type);
const fire = (type: CarActionType) => emit('action', { type, car: props.car });
</script>

<template>
    <div class="flex items-center justify-center gap-1">
        <Tooltip v-if="show('view')">
            <TooltipTrigger as-child>
                <Button type="button" variant="ghost" size="icon" :aria-label="$t('common.view')" @click.stop="fire('view')">
                    <EyeIcon class="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.view') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('edit') && car.can?.update">
            <TooltipTrigger as-child>
                <Button type="button" variant="ghost" size="icon" :aria-label="$t('common.edit')" @click.stop="fire('edit')">
                    <PencilIcon class="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.edit') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('toggleActive') && car.can?.toggleActive">
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :disabled="processing"
                    :aria-label="isActive() ? $t('car.actions.deactivate') : $t('car.actions.activate')"
                    @click.stop="fire('toggleActive')"
                >
                    <Loader2Icon v-if="processing" class="h-4 w-4 animate-spin" />
                    <LockIcon v-else-if="isActive()" class="h-4 w-4 text-amber-600" />
                    <UnlockIcon v-else class="h-4 w-4 text-emerald-600" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {{ isActive() ? $t('car.actions.deactivate') : $t('car.actions.activate') }}
            </TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('delete') && car.can?.delete">
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950"
                    :aria-label="$t('common.delete')"
                    @click.stop="fire('delete')"
                >
                    <Trash2Icon class="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.delete') }}</TooltipContent>
        </Tooltip>
    </div>
</template>
