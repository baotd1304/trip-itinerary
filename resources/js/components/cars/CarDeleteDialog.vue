<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { adminCarRoutes } from '@/lib/car-routes-admin';
import type { AppCar } from '@/types/car';

const props = defineProps<{ open: boolean; car: AppCar | null }>();
const emit = defineEmits<{ (event: 'update:open', value: boolean): void }>();
const form = useForm({ car: '' });
form.transform(() => ({}));

watch(() => props.open, (open) => {
    if (open) {
        form.clearErrors();
    }
});

const submit = () => {
    if (!props.car || form.processing) {
        return;
    }

    form.delete(adminCarRoutes.destroy(props.car.id).url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('car.delete.title') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('car.delete.description', { name: car?.name ?? car?.license_plate ?? '' }) }}
                </DialogDescription>
            </DialogHeader>
            <p v-if="form.errors.car" class="text-sm text-destructive">{{ form.errors.car }}</p>
            <DialogFooter>
                <Button type="button" variant="outline" :disabled="form.processing" @click="emit('update:open', false)">
                    {{ $t('common.cancel') }}
                </Button>
                <Button type="button" variant="destructive" :disabled="form.processing" @click="submit">
                    {{ form.processing ? $t('common.saving') : $t('common.delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
