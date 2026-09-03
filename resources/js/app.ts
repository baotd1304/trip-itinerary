import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import ClientLayout from '@/layouts/client/ClientLayout.vue';
import ClientSimpleLayout from '@/layouts/client/ClientSimpleLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import 'vue-sonner/style.css';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/** Các page dùng ClientLayout (public site) */
const CLIENT_PAGES = ['Home', 'About', 'Contact', 'Pricing', 'Faq'];

/** Các page dùng ClientSimpleLayout (trang lỗi, cảm ơn, form đơn lẻ) */
const CLIENT_SIMPLE_PAGES = ['Thanks', 'NotFound', 'Maintenance'];

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];

            // ⬇️ nhóm client
            case CLIENT_SIMPLE_PAGES.includes(name):
            case name.startsWith('client/simple/'):
                return ClientSimpleLayout;

            case CLIENT_PAGES.includes(name):
            case name.startsWith('home/'):
            case name.startsWith('client/'):
                return ClientLayout;
            
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
