<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { adminUserRoutes } from '@/lib/user-routes-admin';
import type { AppUser } from '@/types/user';

const props = defineProps<{ open: boolean; user: AppUser | null }>();
const emit = defineEmits<{ (e: 'update:open', value: boolean): void }>();
const form = useForm({});

watch(() => props.open, (open) => {
    if (open) {
form.clearErrors();
}
});

const submit = () => {
    if (!props.user || form.processing) {
return;
}

    form.delete(adminUserRoutes.destroy(props.user.id).url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('user.delete.title') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('user.delete.description', { name: user?.name ?? '' }) }}
                </DialogDescription>
            </DialogHeader>
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
