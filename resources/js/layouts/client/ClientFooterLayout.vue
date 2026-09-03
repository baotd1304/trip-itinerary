<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, Mail } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Separator } from '@/components/ui/separator';
import type { NavItem } from '@/types';

type FooterColumn = {
    title: string;
    items: NavItem[];
};

const columns: FooterColumn[] = [
    {
        title: 'Sản phẩm',
        items: [
            { title: 'Chuyến đi', href: '/trips' },
            { title: 'Chi phí', href: '/expenses' },
            { title: 'Bảng giá', href: '/pricing' },
        ],
    },
    {
        title: 'Hỗ trợ',
        items: [
            { title: 'Câu hỏi thường gặp', href: '/faq' },
            { title: 'Liên hệ', href: '/contact' },
        ],
    },
    {
        title: 'Pháp lý',
        items: [
            { title: 'Điều khoản sử dụng', href: '/terms' },
            { title: 'Chính sách bảo mật', href: '/privacy' },
        ],
    },
];

const socialItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/baotd1304/trip-itinerary', icon: FolderGit2 },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
    { title: 'Email', href: 'mailto:hello@example.com', icon: Mail },
];

const year = new Date().getFullYear();
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
                        Lên kế hoạch lịch trình, quản lý xe và chi phí cho mọi chuyến đi của bạn.
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
                            <span class="sr-only">{{ item.title }}</span>
                        </a>
                    </div>
                </div>

                <div v-for="column in columns" :key="column.title">
                    <h3 class="text-sm font-semibold">{{ column.title }}</h3>
                    <ul class="mt-4 space-y-2">
                        <li v-for="item in column.items" :key="item.title">
                            <Link :href="item.href" class="text-sm text-muted-foreground transition-colors hover:text-foreground">
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <Separator class="my-8" />

            <div class="flex flex-col items-center justify-between gap-3 text-sm text-muted-foreground md:flex-row">
                <p>&copy; {{ year }} Trip Itinerary. All rights reserved.</p>
                <slot name="bottom">
                    <p>Built with Laravel, Inertia &amp; shadcn-vue.</p>
                </slot>
            </div>
        </div>
    </footer>
</template>