<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CarBulkAction } from '@/types/car';

const props = defineProps<{
    open: boolean;
    action: CarBulkAction;
    count: number;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (event: 'update:open', value: boolean): void;
    (event: 'confirm'): void;
}>();

const title = computed(() => `car.bulk.${props.action}Title`);
const description = computed(() => `car.bulk.${props.action}Description`);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t(title) }}</DialogTitle>
                <DialogDescription>{{ $t(description, { count }) }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" :disabled="processing" @click="emit('update:open', false)">
                    {{ $t('common.cancel') }}
                </Button>
                <Button
                    type="button"
                    :variant="action === 'delete' ? 'destructive' : 'default'"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ $t('car.bulk.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
