<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Moon, Sun, Tickets } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useAppearance } from '@/composables/useAppearance';
import { useInitials } from '@/composables/useInitials';
import { home, login, register } from '@/routes';
import admin from '@/routes/admin';
// Nếu đã generate Wayfinder cho nhóm route client:
// import client from '@/routes/client';
import type { NavItem } from '@/types';

const page = usePage();
const auth = computed(() => page.props.auth);

const { getInitials } = useInitials();
const { appearance, updateAppearance } = useAppearance();

const mobileOpen = ref(false);

const mainNavItems: NavItem[] = [
    { title: 'Trang chủ', href: '/' },
    {
        title: 'QL Trip Itinerary',
        href: '/',
        icon: Tickets,
        children: [
            {
                title: 'List trips',
                href: admin.trips.index(),
                icon: Tickets,
            },
            {
                title: 'Export trip',
                href: admin.trips.export.index(),
                icon: Tickets,
            },
        ]
    },
    { title: 'Chuyến đi', href: '/trips' },       // client.trips.index()
    { title: 'Chi phí', href: '/expenses' },      // client.expenses.index()
    { title: 'Liên hệ', href: '/contact' },
];

/** href của Wayfinder có thể là string hoặc { url, method } */
const toUrl = (href: NavItem['href']): string => (typeof href === 'string' ? href : href.url);

const isCurrent = (href: NavItem['href']) => {
    const url = toUrl(href);
    return url === '/' ? page.url === '/' : page.url.startsWith(url);
};

const toggleAppearance = () => updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
</script>

<template>
    <header
        class="sticky top-0 z-50 w-full border-b border-sidebar-border/70 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
    >
        <div class="mx-auto flex h-16 w-full max-w-7xl items-center gap-4 px-4 md:px-6">
            <!-- Mobile menu -->
            <Sheet v-model:open="mobileOpen">
                <SheetTrigger as-child>
                    <Button variant="ghost" size="icon" class="lg:hidden">
                        <Menu class="size-5" />
                        <span class="sr-only">Mở menu</span>
                    </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-72 p-6">
                    <SheetHeader class="p-0 text-left">
                        <SheetTitle class="sr-only">Menu điều hướng</SheetTitle>
                        <Link href="/" class="flex items-center gap-2" @click="mobileOpen = false">
                            <AppLogo />
                        </Link>
                    </SheetHeader>

                    <Separator class="my-6" />

                    <nav class="flex flex-col space-y-1">
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.title"
                            :href="item.href"
                            class="rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                            :class="isCurrent(item.href) ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'"
                            @click="mobileOpen = false"
                        >
                            {{ item.title }}
                        </Link>
                    </nav>

                    <template v-if="!auth.user">
                        <Separator class="my-6" />
                        <div class="flex flex-col gap-2">
                            <Button as-child variant="outline">
                                <Link :href="login()">Đăng nhập</Link>
                            </Button>
                            <Button as-child>
                                <Link :href="register()">Đăng ký</Link>
                            </Button>
                        </div>
                    </template>
                </SheetContent>
            </Sheet>

            <!-- Logo -->
            <Link href="/" class="flex items-center gap-2">
                <AppLogo />
            </Link>

            <!-- Desktop nav -->
            <nav class="ml-6 hidden items-center gap-1 lg:flex">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="relative rounded-md px-3 py-2 text-sm font-medium transition-colors hover:text-foreground"
                    :class="isCurrent(item.href) ? 'text-foreground' : 'text-muted-foreground'"
                >
                    {{ item.title }}
                    <span v-if="isCurrent(item.href)" class="absolute inset-x-2 -bottom-[13px] h-0.5 rounded-full bg-primary" />
                </Link>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <Button variant="ghost" size="icon" @click="toggleAppearance">
                    <Sun v-if="appearance === 'dark'" class="size-5" />
                    <Moon v-else class="size-5" />
                    <span class="sr-only">Đổi giao diện sáng/tối</span>
                </Button>

                <template v-if="auth.user">
                    <Button as-child variant="ghost" class="hidden md:inline-flex">
                        <Link :href="admin.dashboard()">Trang quản trị</Link>
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="rounded-full">
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="auth.user.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                                    <AvatarFallback class="rounded-full bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <template v-else>
                    <Button as-child variant="ghost" class="hidden sm:inline-flex">
                        <Link :href="login()">Đăng nhập</Link>
                    </Button>
                    <Button as-child class="hidden sm:inline-flex">
                        <Link :href="register()">Đăng ký</Link>
                    </Button>
                </template>
            </div>
        </div>
    </header>
</template>