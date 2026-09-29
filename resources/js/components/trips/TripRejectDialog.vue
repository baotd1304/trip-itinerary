<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { useFormat } from '@/composables/useFormat';
import type { Trip } from '@/types/trip';

defineProps<{ trip: Trip | null }>();
const open = defineModel<boolean>('open', { required: true });
const { formatDate } = useFormat();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[560px]">
            <DialogHeader>
                <DialogTitle>{{ $t('trip.rejectTrip.title') }}</DialogTitle>
                <DialogDescription>{{ $t('trip.rejectTrip.description') }}</DialogDescription>
            </DialogHeader>

            <Form v-if="trip" :action="`/trips/${trip.id}/reject`" method="patch"
                  v-slot="{ errors, processing }" class="space-y-4" @success="open = false">
                <p class="text-sm text-muted-foreground">
                    <b>#{{ trip.id }} - {{ formatDate(trip.day) }} </b>
                    <br>· {{ trip.origin }} → {{ trip.destination }}
                </p>

                <div class="grid gap-2">
                    <Label for="reject_reason">
                        {{ $t('trip.rejectTrip.reasonLabel') }} <span class="text-red-500">*</span>
                    </Label>
                    <Textarea id="reject_reason" name="reject_reason" rows="3" required minlength="5"
                              :placeholder="$t('trip.rejectTrip.reasonPlaceholder')" />
                    <InputError :message="errors?.reject_reason" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="open = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" variant="destructive" :disabled="processing">
                        {{ $t('trip.rejectTrip.submit') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>