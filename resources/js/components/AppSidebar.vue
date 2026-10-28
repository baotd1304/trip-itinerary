<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeftToLine , LayoutGrid, Users, Car, Tickets, BanknoteArrowDown   } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes/';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

const { t } = useI18n();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('adminSidebar.dashboard'),
        href: admin.dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('adminSidebar.users'),
        href: admin.users.index(),
        icon: Users,
    },
    {
        title: t('adminSidebar.cars'),
        href: admin.cars.index(),
        icon: Car,
    },
    {
        title: t('adminSidebar.trips'),
        href: admin.trips.index(),
        icon: Tickets,
        children: [
            {
                title: t('adminSidebar.tripList'),
                href: admin.trips.index(),
                icon: Tickets,
            },
            {
                title: t('adminSidebar.tripExport'),
                href: admin.trips.export.index(),
                icon: Tickets,
            },
        ],
    },
    {
        title: t('adminSidebar.expenses'),
        href: admin.expenses.index(),
        icon: BanknoteArrowDown,
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('adminSidebar.clientHome'),
        href: home(),
        icon: ArrowLeftToLine,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="admin.dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NotificationBell />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
