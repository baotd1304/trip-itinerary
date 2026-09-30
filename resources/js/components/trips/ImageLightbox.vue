<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const url = defineModel<string | null>({ required: true });
</script>

<template>
    <Dialog :open="!!url" @update:open="(v) => !v && (url = null)">
        <DialogContent class="sm:max-w-[860px]">
            <DialogHeader>
                <DialogTitle>{{ $t('trip.images.preview') }}</DialogTitle>
            </DialogHeader>
            <img v-if="url" :src="url" :alt="$t('trip.images.preview')"
                 class="max-h-[70vh] w-full rounded-md object-contain" />
        </DialogContent>
    </Dialog>
</template>