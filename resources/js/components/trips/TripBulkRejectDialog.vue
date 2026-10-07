<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

defineProps<{ tripIds: number[]; action: string }>();
const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[560px]">
            <DialogHeader>
                <DialogTitle>{{ $t('trip.bulkReview.rejectTitle') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('trip.bulkReview.rejectDescription', { count: tripIds.length }) }}
                </DialogDescription>
            </DialogHeader>

            <Form
                :action="action"
                method="post"
                v-slot="{ errors, processing }"
                class="space-y-4"
                @success="open = false"
            >
                <input type="hidden" name="action" value="reject">
                <input v-for="tripId in tripIds" :key="tripId" type="hidden" name="trip_ids[]" :value="tripId">

                <div class="grid gap-2">
                    <Label for="bulk_reject_reason">
                        {{ $t('trip.rejectTrip.reasonLabel') }} <span class="text-red-500">*</span>
                    </Label>
                    <Textarea
                        id="bulk_reject_reason"
                        name="reject_reason"
                        rows="3"
                        required
                        minlength="5"
                        :placeholder="$t('trip.rejectTrip.reasonPlaceholder')"
                    />
                    <InputError :message="errors?.reject_reason" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="open = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" variant="destructive" :disabled="processing">
                        {{ $t('trip.bulkReview.reject') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
