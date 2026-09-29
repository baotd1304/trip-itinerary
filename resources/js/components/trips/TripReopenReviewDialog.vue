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

const props = defineProps<{ trip: Trip | null; action: 'approve' | 'reject' }>();
const open = defineModel<boolean>('open', { required: true });
const { formatDate } = useFormat();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>
                    {{ action === 'approve' ? $t('trip.reopenReview.approveTitle') : $t('trip.reopenReview.rejectTitle') }}
                </DialogTitle>
                <DialogDescription>
                    {{ action === 'approve'
                        ? $t('trip.reopenReview.approveDescription')
                        : $t('trip.reopenReview.rejectDescription') }}
                </DialogDescription>
            </DialogHeader>

            <Form v-if="trip?.pending_reopen_request"
                  :action="`/reopen-requests/${trip.pending_reopen_request.id}/${action}`"
                  method="patch" v-slot="{ errors, processing }" class="space-y-4"
                  @success="open = false">

                <div class="rounded-md bg-muted/40 p-3 text-sm">
                    <p class="font-medium">
                        #{{ trip.id }} - {{ formatDate(trip.day) }} 
                        <br> {{ trip.origin }} → {{ trip.destination }}
                    </p>
                    <p class="mt-2 text-muted-foreground">
                        {{ $t('trip.reopenReview.requesterSaid', {
                            name: trip.pending_reopen_request.requester?.name ?? '',
                        }) }}
                    </p>
                    <p class="mt-1 italic">"{{ trip.pending_reopen_request.reason }}"</p>
                </div>

                <div class="grid gap-2">
                    <Label for="review_note">
                        {{ $t('trip.reopenReview.noteLabel') }}
                        <span v-if="action === 'reject'" class="text-red-500">*</span>
                    </Label>
                    <Textarea id="review_note" name="review_note" rows="3"
                              :required="action === 'reject'"
                              :placeholder="action === 'approve'
                                  ? $t('trip.reopenReview.approvePlaceholder')
                                  : $t('trip.reopenReview.rejectPlaceholder')" />
                    <InputError :message="errors?.review_note" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="open = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing"
                            :variant="action === 'approve' ? 'default' : 'destructive'">
                        {{ action === 'approve'
                            ? $t('trip.reopenReview.approveSubmit')
                            : $t('trip.reopenReview.rejectSubmit') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>