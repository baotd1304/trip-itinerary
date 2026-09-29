import { computed, type ComputedRef } from 'vue';

export interface PaginationMeta {
    total?: number | null;
    from?: number | null;
    to?: number | null;
    current_page?: number | null;
    last_page?: number | null;
    per_page?: number | null;
}
/**
 * pagination cho cả 3 dạng payload:
 *  - Laravel paginator thuần:      { data, total, from, to, links }
 *  - API Resource collection:      { data, meta: { total, from, to }, links }
 *  - Khối meta tường minh riêng:   tripsMeta = { total, from, to, ... }
 */
export function usePagination(
    source: ComputedRef<unknown> | (() => unknown),
    explicitMeta?: ComputedRef<PaginationMeta | undefined> | (() => PaginationMeta | undefined),
    fallbackCount?: ComputedRef<number> | (() => number),
) {
    const read = <T,>(v: ComputedRef<T> | (() => T)): T =>
        typeof v === 'function' ? v() : v.value;

    /** Gộp meta theo thứ tự ưu tiên: explicit > meta > root */
    const meta = computed<PaginationMeta>(() => {
        const raw = (read(source) ?? {}) as Record<string, unknown>;
        const nested = (raw.meta ?? {}) as PaginationMeta;
        const explicit = explicitMeta ? (read(explicitMeta) ?? {}) : {};

        return { ...raw, ...nested, ...explicit } as PaginationMeta;
    });

    const num = (v: unknown, fb = 0): number => {
        const n = Number(v);
        return Number.isFinite(n) ? n : fb;
    };

    const fallback = computed(() => (fallbackCount ? read(fallbackCount) : 0));

    /** Tổng bản ghi SAU KHI lọc, trên TẤT CẢ các trang */
    const total = computed(() =>
        meta.value.total == null ? fallback.value : num(meta.value.total),
    );

    /** Vị trí bắt đầu của trang hiện tại (dùng cho STT) */
    const startIndex = computed(() => num(meta.value.from, 1));

    const rangeFrom = computed(() => num(meta.value.from, 0));
    const rangeTo = computed(() => num(meta.value.to, 0));

    const currentPage = computed(() => num(meta.value.current_page, 1));
    const lastPage = computed(() => num(meta.value.last_page, 1));
    const perPage = computed(() => num(meta.value.per_page, 10));

    const isEmpty = computed(() => total.value === 0);
    const hasPages = computed(() => lastPage.value > 1);

    return {
        meta,
        total,
        startIndex,
        rangeFrom,
        rangeTo,
        currentPage,
        lastPage,
        perPage,
        isEmpty,
        hasPages,
    };
}