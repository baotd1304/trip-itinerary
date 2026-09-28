import { createI18n } from 'vue-i18n';
import vi from './locales/vi';
import en from './locales/en';

export const SUPPORT_LOCALES = ['vi', 'en'] as const;
export type AppLocale = (typeof SUPPORT_LOCALES)[number];

const LOCALE_KEY = 'app_locale';

function detectLocale(): AppLocale {
    if (typeof window === 'undefined') return 'vi';
    const saved = window.localStorage.getItem(LOCALE_KEY) as AppLocale | null;
    if (saved && SUPPORT_LOCALES.includes(saved)) return saved;

    const htmlLang = document.documentElement.lang?.slice(0, 2) as AppLocale;
    return SUPPORT_LOCALES.includes(htmlLang) ? htmlLang : 'vi';
}

export const i18n = createI18n({
    legacy: false,          // bắt buộc dùng Composition API
    globalInjection: true,  // cho phép dùng $t trong template
    locale: detectLocale(),
    fallbackLocale: 'vi',
    messages: { vi, en },
});

export const persistLocale = (locale: AppLocale) => {
    window.localStorage.setItem(LOCALE_KEY, locale);
    document.documentElement.lang = locale;
};