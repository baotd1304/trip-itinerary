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
                <DialogTitle>{{ $t('trip.reopenRequest.title') }}</DialogTitle>
                <DialogDescription>{{ $t('trip.reopenRequest.description') }}</DialogDescription>
            </DialogHeader>

            <Form v-if="trip" :action="`/trips/${trip.id}/reopen-requests`" method="post"
                  v-slot="{ errors, processing }" class="space-y-4" @success="open = false">
                <p class="text-sm text-muted-foreground">
                    <b>#{{ trip.id }}</b> · {{ formatDate(trip.day) }} · {{ trip.origin }} → {{ trip.destination }}
                </p>

                <div class="grid gap-2">
                    <Label for="reason">
                        {{ $t('trip.reopenRequest.reasonLabel') }} <span class="text-red-500">*</span>
                    </Label>
                    <Textarea id="reason" name="reason" rows="3" required minlength="10"
                              :placeholder="$t('trip.reopenRequest.reasonPlaceholder')" />
                    <InputError :message="errors?.reason" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="open = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">{{ $t('trip.reopenRequest.submit') }}</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>