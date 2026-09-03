import { CalendarDays, Home, MapPinned, Ticket } from 'lucide-vue-next';
import type { NavItem } from '@/types';

export function useClientNav() {
    const mainNavItems: NavItem[] = [
        { title: 'Trang chủ', href: '/', icon: Home },
        { title: 'Chuyến đi', href: '/trips', icon: MapPinned },
        { title: 'Lịch trình', href: '/schedules', icon: CalendarDays },
        { title: 'Đặt chỗ của tôi', href: '/bookings', icon: Ticket },
    ];

    const rightNavItems: NavItem[] = [
        { title: 'Hỗ trợ', href: '/support', icon: undefined },
    ];

    return { mainNavItems, rightNavItems };
}