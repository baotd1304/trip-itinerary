import { router, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';

type FlashType = 'success' | 'error' | 'warning' | 'info';

interface FlashMessage {
    type: FlashType;
    key?: string | null;
    message?: string | null;
    params?: Record<string, unknown> | null;
}

interface FlashBag {
    id: string;
    messages: FlashMessage[];
}

/* ---------- state ở MODULE SCOPE => sống sót qua mọi lần remount ---------- */
let lastFlashId: string | null = null;
let watcherInstalled = false;
let invalidHookInstalled = false;

const DURATION: Record<FlashType, number> = {
    success: 3000,
    info: 3000,
    warning: 4000,
    error: 5000,
};

/**
 * GỌI DUY NHẤT 1 LẦN ở layout gốc (AppLayout.vue).
 * Không gọi lại trong từng page.
 */
export function useFlashToast() {
    const page = usePage();
    const { t, te } = useI18n();

    const render = (m: FlashMessage): string => {
        if (m.key) {
            // có key -> dịch; thiếu bản dịch thì fallback text thô hoặc chính key
            return te(m.key) ? t(m.key, (m.params ?? {}) as Record<string, unknown>) : (m.message ?? m.key);
        }

        if (m.message && te(m.message)) {
            return t(m.message);
        }

        return m.message ?? '';
    };

    const show = (m: FlashMessage, id: string) => {
        const text = render(m);

        if (!text) {
return;
}

        // truyền id -> vue-sonner tự dedupe nếu lỡ bắn trùng
        const options = { id, duration: DURATION[m.type] ?? 3000 };

        switch (m.type) {
            case 'error':
                toast.error(text, options);
                break;
            case 'warning':
                toast.warning(text, options);
                break;
            case 'info':
                toast.info(text, options);
                break;
            default:
                toast.success(text, options);
        }
    };

    /* ---------- 1 watcher duy nhất, dedupe bằng flash.id ---------- */
    if (!watcherInstalled) {
        watcherInstalled = true;

        watch(
            () => page.props.flash as FlashBag | null | undefined,
            (flash) => {
                if (!flash?.id) {
return;
}

                if (flash.id === lastFlashId) {
return;
} // <-- chặn lặp khi filter / partial reload

                lastFlashId = flash.id;
                (flash.messages ?? []).forEach((m, i) => show(m, `${flash.id}:${i}`));
            },
            { immediate: true, deep: true },
        );
    }

    /* ---------- Bắt 403 trả về JSON (không phải Inertia response) ---------- */
    if (!invalidHookInstalled) {
        invalidHookInstalled = true;

        (router.on as (e: string, cb: (event: CustomEvent) => void) => () => void)('invalid', (event) => {
            const status = (event.detail as { response?: { status?: number } } | undefined)?.response?.status;

            if (status === 403) {
                event.preventDefault();
                toast.error(te('common.forbidden') ? t('common.forbidden') : 'Bạn không có quyền thực hiện thao tác này.', {
                    id: 'forbidden',
                });
            }
        });
    }
}