<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/composables/useFlashToast';

type Props = {
    title?: string;
    description?: string;
    /** Link quay lại ở cuối trang */
    backHref?: string;
    backLabel?: string;
};

withDefaults(defineProps<Props>(), {
    backHref: '/',
    backLabel: 'Quay lại trang chủ',
});

useFlashToast();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link href="/" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex size-9 items-center justify-center rounded-md">
                            <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                        <span class="sr-only">{{ title ?? 'Trip Itinerary' }}</span>
                    </Link>

                    <div class="space-y-2 text-center">
                        <h1 v-if="title" class="text-xl font-medium">{{ title }}</h1>
                        <p v-if="description" class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <slot />

                <div class="text-center text-sm text-muted-foreground">
                    <slot name="footer">
                        <Link :href="backHref" class="underline underline-offset-4 hover:text-foreground">
                            {{ backLabel }}
                        </Link>
                    </slot>
                </div>
            </div>
        </div>
    </div>

    <Toaster rich-colors position="top-right" :duration="4000" :visible-toasts="3" />
</template>