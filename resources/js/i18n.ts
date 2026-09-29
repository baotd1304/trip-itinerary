import { createI18n } from 'vue-i18n';
import vi from './locales/vi';
import th from './locales/th';
import en from './locales/en';

export const SUPPORT_LOCALES = ['vi', 'en', 'th'] as const;
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
    globalInjection: true,  // cho phép dùng $t trong template ma k cần khai báo
    locale: detectLocale(),
    fallbackLocale: 'vi',
    messages: { vi, en, th },
});

export const persistLocale = (locale: AppLocale) => {
    window.localStorage.setItem(LOCALE_KEY, locale);
    document.documentElement.lang = locale;
};