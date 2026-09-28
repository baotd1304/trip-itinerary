<script setup lang="ts">
import { computed, watch } from 'vue';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { ImagePlus, Loader2, X } from 'lucide-vue-next';
import { useClientCloudinaryUpload } from '@/composables/useClientCloudinaryUpload';
import { cloudinaryThumb } from '@/composables/useFormat';
import type { TripImage } from '@/types/trip';

const MAX_IMAGES = 10;

defineProps<{ error?: string }>();
const emit = defineEmits<{ 'update:uploading': [boolean]; preview: [string] }>();

/** Danh sách ảnh đã lưu trên server (v-model) */
const existing = defineModel<TripImage[]>({ default: () => [] });

const {
    items: uploadItems,
    uploading,
    uploaded: uploadedImages,
    addFiles,
    remove: removeUpload,
    reset,
    discardOrphans,
} = useClientCloudinaryUpload();

const total = computed(() => existing.value.length + uploadItems.value.length);
const remaining = computed(() => Math.max(0, MAX_IMAGES - total.value));

watch(uploading, (v) => emit('update:uploading', v), { immediate: true });

const onPickFiles = async (e: Event) => {
    const input = e.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';
    if (files.length) await addFiles(files, remaining.value);
};

const removeExisting = (img: TripImage) => {
    existing.value = existing.value.filter((i) => i.id !== img.id);
};

defineExpose({ reset, discardOrphans });
</script>

<template>
    <div class="grid gap-3 rounded-md border p-3">
        <div class="flex items-center justify-between">
            <Label class="font-medium">{{ $t('trip.images.label') }}</Label>
            <span class="text-xs text-muted-foreground">
                {{ $t('trip.images.counter', { current: total, max: MAX_IMAGES }) }}
            </span>
        </div>

        <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed
                      px-4 py-6 text-sm text-muted-foreground transition hover:bg-muted/50"
               :class="remaining === 0 ? 'pointer-events-none opacity-50' : ''">
            <ImagePlus class="h-5 w-5" />
            <span>{{ $t('trip.images.dropHint') }}</span>
            <input type="file" class="hidden" multiple
                   accept="image/jpeg,image/png,image/webp,image/heic"
                   :disabled="remaining === 0" @change="onPickFiles" />
        </label>

        <div v-if="total" class="grid grid-cols-4 gap-3 sm:grid-cols-5">
            <!-- Ảnh đã lưu -->
            <div v-for="img in existing" :key="'old-' + img.id"
                 class="group relative aspect-square overflow-hidden rounded-md border">
                <img :src="cloudinaryThumb(img.url)" :alt="$t('trip.images.alt', { id: img.id })"
                     class="h-full w-full cursor-zoom-in object-cover"
                     @click="emit('preview', img.url)" />
                <button type="button"
                        class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0
                               transition group-hover:opacity-100"
                        @click="removeExisting(img)">
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Ảnh đang/đã upload -->
            <div v-for="item in uploadItems" :key="item.uid"
                 class="group relative aspect-square overflow-hidden rounded-md border"
                 :class="item.status === 'error' ? 'border-red-500' : ''">
                <img :src="item.result ? cloudinaryThumb(item.result.url) : item.preview" :alt="item.name"
                     class="h-full w-full object-cover"
                     :class="item.status === 'uploading' ? 'opacity-50' : 'cursor-zoom-in'"
                     @click="item.result && emit('preview', item.result.url)" />

                <div v-if="item.status === 'uploading'"
                     class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/40 text-white">
                    <Loader2 class="h-4 w-4 animate-spin" />
                    <span class="text-xs font-medium">{{ item.progress }}%</span>
                </div>

                <div v-if="item.status === 'error'"
                     class="absolute inset-x-0 bottom-0 bg-red-600/90 px-1 py-0.5 text-[10px] leading-tight text-white">
                    {{ item.error }}
                </div>

                <button type="button"
                        class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0
                               transition group-hover:opacity-100"
                        @click="removeUpload(item.uid)">
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <!-- Hidden inputs: ảnh mới upload -->
        <template v-for="(img, i) in uploadedImages" :key="'up-' + img.public_id">
            <input type="hidden" :name="`images[${i}][public_id]`" :value="img.public_id" />
            <input type="hidden" :name="`images[${i}][url]`" :value="img.url" />
            <input type="hidden" :name="`images[${i}][format]`" :value="img.format ?? ''" />
            <input type="hidden" :name="`images[${i}][width]`" :value="img.width ?? ''" />
            <input type="hidden" :name="`images[${i}][height]`" :value="img.height ?? ''" />
            <input type="hidden" :name="`images[${i}][bytes]`" :value="img.bytes ?? ''" />
        </template>

        <!-- LUÔN gửi, kể cả khi xoá hết ảnh -->
        <input type="hidden" name="images_synced" value="1" />
        <input v-for="img in existing" :key="'keep-' + img.id"
               type="hidden" name="kept_image_ids[]" :value="img.id" />

        <InputError :message="error" />
    </div>
</template>