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
import { adminExpenseRoutes } from '@/lib/expense-routes-admin';
import type { AppExpense } from '@/types/expense';

const props = defineProps<{ open: boolean; expense: AppExpense | null }>();
const emit = defineEmits<{ (event: 'update:open', value: boolean): void }>();
const form = useForm({ expense: '' });
form.transform(() => ({}));

watch(() => props.open, (open) => {
    if (open) {
        form.clearErrors();
    }
});

const submit = () => {
    if (!props.expense || form.processing) {
        return;
    }

    form.delete(adminExpenseRoutes.destroy(props.expense.id).url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('expense.delete.title') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('expense.delete.description', { name: expense?.name ?? '' }) }}
                </DialogDescription>
            </DialogHeader>
            <p v-if="form.errors.expense" class="text-sm text-destructive">{{ form.errors.expense }}</p>
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
