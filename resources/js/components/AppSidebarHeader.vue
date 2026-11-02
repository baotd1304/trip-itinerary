<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';
import LocaleSwitcher from './LocaleSwitcher.vue';
import { ChevronDown, Menu, Moon, Sun } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();
const toggleAppearance = () => updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 flex-row justify-between w-full">
            <div class="flex items-center gap-2 flex-row justify-between">
                <SidebarTrigger class="-ml-1" />
                <template v-if="breadcrumbs && breadcrumbs.length > 0">
                    <Breadcrumbs :breadcrumbs="breadcrumbs" />
                </template>
            </div>
            <div class="flex items-center gap-2 flex-row justify-between">
                <LocaleSwitcher class="ml-auto" />
                <Button variant="ghost" size="icon" @click="toggleAppearance">
                    <Sun v-if="appearance === 'dark'" class="size-5" />
                    <Moon v-else class="size-5" />
                    <span class="sr-only">{{ $t('clientLayout.toggleAppearance') }}</span>
                </Button>
            </div>
        </div>
    </header>
</template>
