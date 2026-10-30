<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/composables/useFlashToast';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { t, te } = useI18n();
const localizedBreadcrumbs = computed(() =>
    props.breadcrumbs.map((breadcrumb) => ({
        ...breadcrumb,
        title: te(breadcrumb.title) ? t(breadcrumb.title) : breadcrumb.title,
    })),
);

useFlashToast();
</script>

<template>
    <AppLayout :breadcrumbs="localizedBreadcrumbs">
        <slot />
    </AppLayout>
    <Toaster rich-colors position="top-right" :duration="3000" close-button :visible-toasts="3"/>

</template>
