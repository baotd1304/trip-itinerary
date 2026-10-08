<!-- components/LocaleSwitcher.vue -->
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
        label: 'VI',
        country: 'vn',
    },
    th: {
        label: 'TH',
        country: 'th',
    },
    en: {
        label: 'EN',
        country: 'gb',
    },
};
const currentLanguage = () =>
    LANGUAGE_CONFIG[locale.value] ?? {
        label: locale.value.toUpperCase(),
        country: locale.value,
    };

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="xs"
                class="h-8 w-8 p-0"
                :aria-label="$t('clientLayout.languageSelection')"
            >
                <span
                    :class="[
                        'fi',
                        `fi-${currentLanguage().country}`,
                        'text-base',
                    ]"
                />

                <span class="hidden xs:inline">
                    {{ currentLanguage().label }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="start" class="w-auto min-w-0 p-1">
            <DropdownMenuItem
                v-for="l in locales"
                :key="l"
                class="flex h-7 w-10 min-w-0 cursor-pointer items-center gap-2 p-0"
                :class="{ 'bg-accent': locale === l }"
                @click="setLocale(l)"
            >
                <span
                    :class="[
                        'fi',
                        `fi-${LANGUAGE_CONFIG[l]?.country ?? l}`,
                        'text-base',
                    ]"
                />
                <span
                    v-if="locale === l"
                    class="ml-auto"
                    aria-hidden="true"
                >✓</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
    
</template>