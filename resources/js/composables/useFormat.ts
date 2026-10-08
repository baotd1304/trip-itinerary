import { useI18n } from 'vue-i18n';

const INTL_LOCALE: Record<string, string> = { vi: 'vi-VN', en: 'en-US', th: 'th-TH' };

export function useFormat() {
    const { locale } = useI18n();
    const intl = () => INTL_LOCALE[locale.value] ?? 'vi-VN';

    const formatDate = (date?: string | null) => {
        if (!date) {
            return '';
        }

        const d = new Date(date);

        if (Number.isNaN(d.getTime())) {
            return String(date);
        }

        return new Intl.DateTimeFormat(intl(), {
            day: '2-digit', month: '2-digit', year: 'numeric',
        }).format(d);
    };

    const formatDateTime = (value?: string | null) => {
        if (!value) {
            return '';
        }

        const d = new Date(value);

        if (Number.isNaN(d.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat(intl(), {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(d);
    };

    const formatMoney = (value?: number | null) =>
        new Intl.NumberFormat(intl(), { style: 'currency', currency: 'VND' })
            .format(Number(value) || 0);

    return { formatDate, formatDateTime, formatMoney };
}

/* Helper thuần, không phụ thuộc locale */
export const toDateInput = (v?: string | null) => (v ? String(v).slice(0, 10) : '');
export const toTimeInput = (v?: string | null) => (v ? String(v).slice(0, 5) : '');
export const cloudinaryThumb = (url: string) =>
    url.replace('/upload/', '/upload/c_fill,w_240,h_240,q_auto,f_auto/');