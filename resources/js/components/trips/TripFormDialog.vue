<!-- dialog view/create/edit -->
 <script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Form } from '@inertiajs/vue3';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Loader2, Trash2 } from 'lucide-vue-next';

import TripStatusBadge from './TripStatusBadge.vue';
import TripContextBanners from './TripContextBanners.vue';
import TripFormFields from './TripFormFields.vue';
import TripImageUploader from './TripImageUploader.vue';
import TripDetailView from './TripDetailView.vue';
import TripActions from './TripActions.vue';

import trips from '@/routes/client/trips';
import { useFormat } from '@/composables/useFormat';
import { submitWarningKey } from '@/lib/trip-status';
import { deleteHintKey } from '@/lib/trip-permissions';
import { DIALOG_ACTIONS } from '@/lib/trip-action-presets.js';
import { emptyTripForm, mapTripToForm } from '@/composables/useTripModel';
import type {
    Advisor, Car, DialogMode, Driver, Trip, TripActionPayload, TripImage, TripActionType
} from '@/types/trip';
const props = defineProps<{
    mode: DialogMode;
    trip: Trip | null;
    cars: Car[];
    advisors: Advisor[];
    drivers: Driver[];
    isDriver: boolean;
    isAdvisor: boolean;
    authUser?: { id: number; name: string } | null;
    confirmingId: number | null;
}>();

const emit = defineEmits<{
    action: [TripActionPayload];
    preview: [string];
    'switch-mode': [DialogMode];
}>();

const open = defineModel<boolean>('open', { required: true });
const { formatDate } = useFormat();

const model = ref(emptyTripForm());
const existingImages = ref<TripImage[]>([]);
const isUploading = ref(false);
const uploaderRef = ref<InstanceType<typeof TripImageUploader> | null>(null);

const isCreate = computed(() => props.mode === 'create');
const isView = computed(() => props.mode === 'view');
const isEdit = computed(() => props.mode === 'edit');

/** Dialog chi tiết: đủ mọi thao tác, trừ nút "Xem" (đang ở trong chính màn xem) */
// const DIALOG_ACTIONS: TripActionType[] = [
//     'edit', 'delete', 'request-reopen',
//     'approve-reopen', 'reject-reopen',
//     'confirm', 'reject',
// ];

/* Đồng bộ model mỗi khi mở dialog hoặc đổi trip/mode */
watch(
    () => [open.value, props.mode, props.trip?.id],
    () => {
        if (!open.value) return;

        if (props.trip && !isCreate.value) {
            model.value = mapTripToForm(props.trip);
            existingImages.value = [...(props.trip.images ?? [])];
        } else {
            model.value = emptyTripForm();
            existingImages.value = [];
            if (props.isDriver && props.authUser?.id) model.value.driver_id = props.authUser.id;
            if (props.isAdvisor && props.authUser?.id) model.value.advisor_id = props.authUser.id;
        }
        uploaderRef.value?.reset();
    },
    { immediate: true },
);

const title = computed(() => {
    if (isCreate.value) return $tKey('trip.dialog.createTitle');
    const prefix = isView.value ? $tKey('trip.dialog.viewTitle') : $tKey('trip.dialog.editTitle');
    return `${prefix} #${model.value.id} · ${formatDate(model.value.day)}`;
});

/* Dùng t() trong script */
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
function $tKey(key: string) { return t(key); }

const formProps = computed(() =>
    isCreate.value ? trips.store.form() : trips.update.form(model.value.id));

const submitWarning = computed(() => {
    if (!isEdit.value || !props.isDriver) return null;
    const key = submitWarningKey(model.value.status);
    return key ? t(key) : null;
});

const close = (discard = true) => {
    if (discard) uploaderRef.value?.discardOrphans();
    uploaderRef.value?.reset();
    existingImages.value = [];
    open.value = false;
};

const backToView = () => {
    uploaderRef.value?.discardOrphans();
    emit('switch-mode', 'view');
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[92vh] flex-col sm:max-w-[760px]">
            <DialogHeader>
                <DialogTitle class="flex flex-wrap items-center gap-2">
                    <span>{{ title }}</span>
                    <TripStatusBadge v-if="!isCreate" :status="model.status" />
                </DialogTitle>
                <DialogDescription v-if="isView">
                    {{ $t('trip.dialog.viewDescription') }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto px-1">
                <TripContextBanners v-if="!isCreate" class="mb-4"
                                    :model="model" :show-edit-hint="isEdit" />

                <!-- ===== VIEW ===== -->
                <TripDetailView v-if="isView"
                                :model="model" :images="existingImages" :cars="cars"
                                @preview="(url) => $emit('preview', url)" />

                <!-- ===== CREATE / EDIT ===== -->
                <Form v-else
                      :key="mode + '-' + model.id"
                      v-bind="formProps"
                      v-slot="{ errors, processing }"
                      class="space-y-4"
                      @success="close(false)">

                    <TripFormFields v-model="model"
                                    :cars="cars" :advisors="advisors" :drivers="drivers"
                                    :is-driver="isDriver" :auth-user="authUser" :errors="errors" />

                    <TripImageUploader ref="uploaderRef"
                                       v-model="existingImages"
                                       :error="errors?.images"
                                       @update:uploading="(v) => (isUploading = v)"
                                       @preview="(url) => $emit('preview', url)" />

                    <div class="grid gap-2">
                        <Label for="f-note">{{ $t('trip.form.note') }}</Label>
                        <Input id="f-note" v-model="model.note" name="note" />
                        <InputError :message="errors?.note" />
                    </div>

                    <div v-if="submitWarning"
                         class="rounded-md border px-3 py-2 text-sm"
                         :class="model.status === 'editing'
                             ? 'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-700 dark:bg-sky-950 dark:text-sky-200'
                             : 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200'">
                        {{ submitWarning }}
                    </div>

                    <DialogFooter class="gap-2 sm:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <Button v-if="isEdit && trip?.can?.delete" type="button" variant="destructive" size="sm"
                                    :disabled="processing" :title="$t(deleteHintKey(trip))"
                                    @click="trip && $emit('action', { type: 'delete', trip })">
                                <Trash2 class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.delete') }}
                            </Button>
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <Button type="button" variant="outline" :disabled="processing"
                                    @click="isEdit && trip ? backToView() : close(true)">
                                {{ isEdit && trip ? $t('trip.actions.backToView') : $t('common.cancel') }}
                            </Button>
                            <Button type="submit" :disabled="processing || isUploading">
                                <Loader2 v-if="processing || isUploading" class="mr-2 h-4 w-4 animate-spin" />
                                {{ isUploading
                                    ? $t('common.uploadingImages')
                                    : (isCreate ? $t('trip.actions.submitCreate') : $t('trip.actions.submitUpdate')) }}
                            </Button>
                        </div>
                    </DialogFooter>
                </Form>
            </div>

            <!-- Footer chế độ xem: toàn bộ hành động theo quyền -->
            <DialogFooter v-if="isView && trip" class="gap-2 border-t pt-4 sm:justify-between">
                <TripActions
                    :trip="trip"
                    variant="dialog"
                    show-empty-hint
                    :only="DIALOG_ACTIONS"
                    :confirming="confirmingId === trip.id"
                    @action="(p) => $emit('action', p)" />
                <Button variant="outline" @click="close(false)">{{ $t('common.close') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>