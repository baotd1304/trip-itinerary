<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeftToLine , LayoutGrid, Users, Car, Tickets, BanknoteArrowDown   } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import { home } from '@/routes/';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: admin.users.index(),
        icon: Users,
    },
    {
        title: 'QL Cars',
        href: admin.cars.index(),
        icon: Car,
    },
    {
        title: 'QL Trip Itinerary',
        href: admin.trips.index(),
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
    {
        title: 'QL Expenses',
        href: admin.expenses.index(),
        icon: BanknoteArrowDown ,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Trang Client',
        href: home(),
        icon: ArrowLeftToLine ,
    },
    
];
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
