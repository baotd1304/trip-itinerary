<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, Mail } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/AppLogo.vue';
import { Separator } from '@/components/ui/separator';
import type { NavItem } from '@/types';

type FooterColumn = {
    title: string;
    items: NavItem[];
};

const columns: FooterColumn[] = [
    {
        title: 'clientLayout.footer.product',
        items: [
            { title: 'clientLayout.nav.trips', href: '/trips' },
            { title: 'clientLayout.nav.expenses', href: '/expenses' },
            { title: 'clientLayout.footer.pricing', href: '/pricing' },
        ],
    },
    {
        title: 'clientLayout.footer.support',
        items: [
            { title: 'clientLayout.footer.faq', href: '/faq' },
            { title: 'clientLayout.nav.contact', href: '/contact' },
        ],
    },
    {
        title: 'clientLayout.footer.legal',
        items: [
            { title: 'clientLayout.footer.terms', href: '/terms' },
            { title: 'clientLayout.footer.privacy', href: '/privacy' },
        ],
    },
];

const socialItems: NavItem[] = [
    { title: 'clientLayout.footer.repository', href: 'https://github.com/baotd1304/trip-itinerary', icon: FolderGit2 },
    { title: 'clientLayout.footer.documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
    { title: 'clientLayout.footer.email', href: 'mailto:hello@example.com', icon: Mail },
];

const year = new Date().getFullYear();
const { t } = useI18n();
</script>

<template>
    <footer class="border-t border-sidebar-border/70 bg-background">
        <div class="mx-auto w-full max-w-7xl px-4 py-12 md:px-6">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <Link href="/" class="flex items-center gap-2">
                        <AppLogo />
                    </Link>
                    <p class="mt-4 max-w-sm text-sm text-muted-foreground">
                        {{ $t('clientLayout.footer.description') }}
                    </p>
                    <div class="mt-6 flex items-center gap-3">
                        <a
                            v-for="item in socialItems"
                            :key="item.title"
                            :href="typeof item.href === 'string' ? item.href : item.href.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                        >
                            <component :is="item.icon" v-if="item.icon" class="size-4" />
                            <span class="sr-only">{{ t(item.title) }}</span>
                        </a>
                    </div>
                </div>

                <div v-for="column in columns" :key="column.title">
                    <h3 class="text-sm font-semibold">{{ t(column.title) }}</h3>
                    <ul class="mt-4 space-y-2">
                        <li v-for="item in column.items" :key="item.title">
                            <Link :href="item.href" class="text-sm text-muted-foreground transition-colors hover:text-foreground">
                                {{ t(item.title) }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <Separator class="my-8" />

            <div class="flex flex-col items-center justify-between gap-3 text-sm text-muted-foreground md:flex-row">
                <p>&copy; {{ year }} Trip Itinerary. {{ $t('clientLayout.footer.rightsReserved') }}</p>
                <slot name="bottom">
                    <p>{{ $t('clientLayout.footer.builtWith') }}</p>
                </slot>
            </div>
        </div>
    </footer>
</template>