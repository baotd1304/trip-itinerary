<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useTripRoutes } from '@/composables/useTripRoutes';
import { useFormat } from '@/composables/useFormat';
import type { Trip } from '@/types/trip';
    
defineProps<{ trip: Trip | null }>();
const open = defineModel<boolean>('open', { required: true });
const { formatDate } = useFormat();
const routes = useTripRoutes();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[560px]">
            <DialogHeader>
                <DialogTitle>{{ $t('trip.deleteDialog.title') }}</DialogTitle>
                <DialogDescription>{{ $t('trip.deleteDialog.description') }}</DialogDescription>
            </DialogHeader>

            <Form v-if="trip" v-bind="routes.destroy.form(trip.id)" v-slot="{ processing }"
                  class="space-y-4" @success="open = false">
                <p class="text-sm text-muted-foreground">
                    {{ $t('trip.deleteDialog.confirmText', {
                        id: trip.id,
                        day: formatDate(trip.day),
                        route: `${trip.origin} → ${trip.destination}`,
                    }) }}
                </p>
                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="open = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" variant="destructive" :disabled="processing">
                        {{ $t('common.delete') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>