import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import ClientLayout from '@/layouts/client/ClientLayout.vue';
import ClientSimpleLayout from '@/layouts/client/ClientSimpleLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { createApp, h } from 'vue'
import { vCan } from './directives/can'
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

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)

        // Đăng ký directive toàn cục
        app.directive('can', vCan)

        // (Optional) Đăng ký như global property để dùng trong template
        app.config.globalProperties.$can = vCan.check
        app.config.globalProperties.$cannot = (value: any) => !vCan.check(value)

        app.mount(el!)
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
