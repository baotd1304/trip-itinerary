<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/composables/useFlashToast';
import ClientFooterLayout from '@/layouts/client/ClientFooterLayout.vue';
import ClientHeaderLayout from '@/layouts/client/ClientHeaderLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    /** false = nội dung full-width (dùng cho landing page có hero) */
    container?: boolean;
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    container: true,
});

useFlashToast();
</script>

<template>
    <AppShell class="min-h-svh flex-col">
        <ClientHeaderLayout />

        <div v-if="breadcrumbs.length > 1" class="border-b border-sidebar-border/70 bg-muted/30">
            <div class="mx-auto flex w-full max-w-7xl items-center px-4 py-3 md:px-6">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <AppContent class="flex-1 overflow-x-hidden">
            <slot v-if="!container" />
            <div v-else class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 md:px-6">
                <slot />
            </div>
        </AppContent>

        <ClientFooterLayout />
    </AppShell>

    <Toaster rich-colors position="top-right" :duration="4000" :visible-toasts="3" />
</template>