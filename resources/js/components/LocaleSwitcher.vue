<!-- components/LocaleSwitcher.vue -->
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';

const { locale, setLocale, locales } = useLocale();

const LANGUAGE_CONFIG: Record<
    string,
    {
        label: string;
        country: string;
    }
> = {
    vi: {
        label: '',
        country: 'vn',
    },
    en: {
        label: '',
        country: 'gb',
    },
};
</script>

<template>
    <div class="flex items-center gap-1">
        <Button
            v-for="l in locales"
            :key="l"
            size="xs"
            :variant="locale === l ? 'secondary' : 'ghost'"
            :aria-label="`Chuyển sang ${LANGUAGE_CONFIG[l]?.label ?? l}`"
            :aria-pressed="locale === l"
            @click="setLocale(l)"
        >
            <span
                :class="`fi fi-${LANGUAGE_CONFIG[l]?.country ?? l}`"
                class="text-base"
            />

            <span>
                {{ LANGUAGE_CONFIG[l]?.label ?? l.toUpperCase() }}
            </span>
        </Button>
    </div>
</template>