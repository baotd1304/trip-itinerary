import { useI18n } from 'vue-i18n';
import { persistLocale, SUPPORT_LOCALES, type AppLocale } from '@/i18n';
import { router } from '@inertiajs/vue3';

export function useLocale() {
    const { locale } = useI18n();

    const setLocale = (value: AppLocale) => {
        locale.value = value;
        persistLocale(value);
        // Nếu muốn đồng bộ với Laravel (validation messages phía server):
        router.post('/locale', { locale: value }, { preserveScroll: true, preserveState: true, only: [] });
    };

    return { locale, setLocale, locales: SUPPORT_LOCALES };
}