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
import { adminExpenseRoutes } from '@/lib/expense-routes-admin';
import type { AppExpense, ExpenseDialogMode } from '@/types/expense';

const props = defineProps<{
    open: boolean;
    mode: ExpenseDialogMode;
    expense: AppExpense | null;
}>();

const emit = defineEmits<{
    (event: 'update:open', value: boolean): void;
    (event: 'switch-mode', mode: ExpenseDialogMode): void;
}>();

const form = useForm({
    name: '',
    overkm_rate: null as number | null,
    overtime_rate: null as number | null,
    overnight_rate: null as number | null,
    holiday_rate: null as number | null,
    note: '',
    is_active: true,
});

const isView = computed(() => props.mode === 'view');
const isCreate = computed(() => props.mode === 'create');
const canEditActive = computed(() =>
    isCreate.value || props.expense?.can?.toggleActive === true,
);
type ExpenseRateKey = 'overkm_rate' | 'overtime_rate' | 'overnight_rate' | 'holiday_rate';
const rateFields: { key: ExpenseRateKey; label: string }[] = [
    { key: 'overkm_rate', label: 'expense.fields.overkmRate' },
    { key: 'overtime_rate', label: 'expense.fields.overtimeRate' },
    { key: 'overnight_rate', label: 'expense.fields.overnightRate' },
    { key: 'holiday_rate', label: 'expense.fields.holidayRate' },
];

watch(
    () => [props.open, props.expense, props.mode],
    () => {
        if (!props.open) {
            return;
        }

        form.clearErrors();
        form.defaults({
            name: props.expense?.name ?? '',
            overkm_rate: props.expense?.overkm_rate ?? null,
            overtime_rate: props.expense?.overtime_rate ?? null,
            overnight_rate: props.expense?.overnight_rate ?? null,
            holiday_rate: props.expense?.holiday_rate ?? null,
            note: props.expense?.note ?? '',
            is_active: props.expense ? props.expense.is_active === true || props.expense.is_active === 1 : true,
        });
        form.reset();
    },
    { immediate: true },
);

form.transform((data) => ({
    ...data,
    is_active: canEditActive.value ? data.is_active : undefined,
}));

const setRate = (key: ExpenseRateKey, value: string | number) => {
    form[key] = value === '' ? null : Number(value);
};

const submit = () => {
    if (form.processing) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (isCreate.value) {
        form.post(adminExpenseRoutes.store().url, options);

        return;
    }

    if (props.expense) {
        form.put(adminExpenseRoutes.update(props.expense.id).url, options);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isCreate ? $t('expense.dialog.createTitle') : isView ? $t('expense.dialog.viewTitle') : $t('expense.dialog.editTitle') }}
                </DialogTitle>
                <DialogDescription>{{ $t('expense.dialog.description') }}</DialogDescription>
            </DialogHeader>

            <div class="grid flex-1 gap-4 overflow-y-auto px-1 sm:grid-cols-2">
                <div class="space-y-1.5 sm:col-span-2">
                    <Label for="expense-name">{{ $t('expense.fields.name') }}</Label>
                    <Input id="expense-name" v-model="form.name" maxlength="255" :disabled="isView" />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>
                <div
                    v-for="field in rateFields"
                    :key="field.key"
                    class="space-y-1.5"
                >
                    <Label :for="`expense-${field.key}`">{{ $t(field.label) }}</Label>
                    <Input
                        :id="`expense-${field.key}`"
                        :model-value="form[field.key] ?? ''"
                        type="number"
                        min="0"
                        step="1"
                        :disabled="isView"
                        @update:model-value="setRate(field.key, $event)"
                    />
                    <p v-if="form.errors[field.key]" class="text-xs text-destructive">{{ form.errors[field.key] }}</p>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <Label for="expense-note">{{ $t('expense.fields.note') }}</Label>
                    <textarea
                        id="expense-note"
                        v-model="form.note"
                        maxlength="255"
                        rows="3"
                        :disabled="isView"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <p v-if="form.errors.note" class="text-xs text-destructive">{{ form.errors.note }}</p>
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <Switch id="expense-active" v-model="form.is_active" :disabled="isView || !canEditActive" />
                    <Label for="expense-active">{{ $t('expense.fields.isActive') }}</Label>
                    <p v-if="form.errors.is_active" class="text-xs text-destructive">{{ form.errors.is_active }}</p>
                </div>
                <div
                    v-if="isView && expense"
                    class="grid gap-2 rounded-md bg-muted/40 p-3 text-xs text-muted-foreground sm:col-span-2 sm:grid-cols-2"
                >
                    <div>{{ $t('expense.fields.id') }}: <b>{{ expense.id }}</b></div>
                    <div>{{ $t('expense.fields.createdBy') }}: <b>{{ expense.creator?.name ?? '—' }}</b></div>
                    <div>{{ $t('expense.fields.createdAt') }}: <b>{{ expense.created_at ?? '—' }}</b></div>
                    <div>{{ $t('expense.fields.updatedAt') }}: <b>{{ expense.updated_at ?? '—' }}</b></div>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('update:open', false)">
                    {{ $t('common.close') }}
                </Button>
                <Button v-if="isView && expense?.can?.update" type="button" @click="emit('switch-mode', 'edit')">
                    {{ $t('common.edit') }}
                </Button>
                <Button v-if="!isView" type="button" :disabled="form.processing" @click="submit">
                    {{ isCreate ? $t('common.create') : form.processing ? $t('common.saving') : $t('common.save') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
