<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { adminCarRoutes } from '@/lib/car-routes-admin';
import type { AppCar, CarDialogMode } from '@/types/car';

const props = defineProps<{
    open: boolean;
    mode: CarDialogMode;
    car: AppCar | null;
}>();

const emit = defineEmits<{
    (event: 'update:open', value: boolean): void;
    (event: 'switch-mode', mode: CarDialogMode): void;
}>();

const form = useForm({
    name: '',
    brand: '',
    model: '',
    year: '',
    license_plate: '',
    owner: '',
    is_active: true,
});

const isView = computed(() => props.mode === 'view');
const isCreate = computed(() => props.mode === 'create');

watch(
    () => [props.open, props.car, props.mode],
    () => {
        if (!props.open) {
            return;
        }

        form.clearErrors();
        form.defaults({
            name: props.car?.name ?? '',
            brand: props.car?.brand ?? '',
            model: props.car?.model ?? '',
            year: props.car?.year?.toString() ?? '',
            license_plate: props.car?.license_plate ?? '',
            owner: props.car?.owner ?? '',
            is_active: props.car ? props.car.is_active === true || props.car.is_active === 1 : true,
        });
        form.reset();
    },
    { immediate: true },
);

const submit = () => {
    if (form.processing) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (isCreate.value) {
        form.post(adminCarRoutes.store().url, options);

        return;
    }

    if (props.car) {
        form.put(adminCarRoutes.update(props.car.id).url, options);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isCreate ? $t('car.dialog.createTitle') : isView ? $t('car.dialog.viewTitle') : $t('car.dialog.editTitle') }}
                </DialogTitle>
                <DialogDescription>{{ $t('car.dialog.description') }}</DialogDescription>
            </DialogHeader>

            <div class="grid flex-1 gap-4 overflow-y-auto px-1 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label for="car-name">{{ $t('car.fields.name') }}</Label>
                    <Input id="car-name" v-model="form.name" maxlength="255" :disabled="isView" />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>
                <div class="space-y-1.5">
                    <Label for="car-license-plate">{{ $t('car.fields.licensePlate') }}</Label>
                    <Input id="car-license-plate" v-model="form.license_plate" maxlength="20" :disabled="isView" />
                    <p v-if="form.errors.license_plate" class="text-xs text-destructive">{{ form.errors.license_plate }}</p>
                </div>
                <div class="space-y-1.5">
                    <Label for="car-brand">{{ $t('car.fields.brand') }}</Label>
                    <Input id="car-brand" v-model="form.brand" maxlength="255" :disabled="isView" />
                    <p v-if="form.errors.brand" class="text-xs text-destructive">{{ form.errors.brand }}</p>
                </div>
                <div class="space-y-1.5">
                    <Label for="car-model">{{ $t('car.fields.model') }}</Label>
                    <Input id="car-model" v-model="form.model" maxlength="255" :disabled="isView" />
                    <p v-if="form.errors.model" class="text-xs text-destructive">{{ form.errors.model }}</p>
                </div>
                <div class="space-y-1.5">
                    <Label for="car-year">{{ $t('car.fields.year') }}</Label>
                    <Input id="car-year" v-model.number="form.year" type="number" min="1900" :max="new Date().getFullYear()" :disabled="isView" />
                    <p v-if="form.errors.year" class="text-xs text-destructive">{{ form.errors.year }}</p>
                </div>
                <div class="space-y-1.5">
                    <Label for="car-owner">{{ $t('car.fields.owner') }}</Label>
                    <Input id="car-owner" v-model="form.owner" maxlength="255" :disabled="isView" />
                    <p v-if="form.errors.owner" class="text-xs text-destructive">{{ form.errors.owner }}</p>
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <Switch id="car-active" v-model="form.is_active" :disabled="isView" />
                    <Label for="car-active">{{ $t('car.fields.isActive') }}</Label>
                    <p v-if="form.errors.is_active" class="text-xs text-destructive">{{ form.errors.is_active }}</p>
                </div>
                <div v-if="isView && car" class="grid gap-2 rounded-md bg-muted/40 p-3 text-xs text-muted-foreground sm:col-span-2 sm:grid-cols-2">
                    <div>{{ $t('car.fields.id') }}: <b>{{ car.id }}</b></div>
                    <div>{{ $t('car.fields.createdAt') }}: <b>{{ car.created_at ?? '—' }}</b></div>
                    <div>{{ $t('car.fields.updatedAt') }}: <b>{{ car.updated_at ?? '—' }}</b></div>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('update:open', false)">
                    {{ $t('common.close') }}
                </Button>
                <Button v-if="isView && car?.can?.update" type="button" @click="emit('switch-mode', 'edit')">
                    {{ $t('common.edit') }}
                </Button>
                <Button v-if="!isView" type="button" :disabled="form.processing" @click="submit">
                    {{ isCreate ? $t('common.create') : $t('common.save') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
