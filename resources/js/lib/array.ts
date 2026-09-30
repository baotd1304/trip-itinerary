/** Chuẩn hoá: [...] | {data:[...]} | {data:{...}} | {0:{},1:{}} → T[] */
export const toArray = <T,>(src: unknown): T[] => {
    if (!src) return [];
    if (Array.isArray(src)) return src as T[];

    if (typeof src === 'object') {
        const d = (src as any).data;
        if (Array.isArray(d)) return d as T[];
        if (d && typeof d === 'object') return Object.values(d) as T[];
        return Object.values(src as any).filter((v) => v && typeof v === 'object') as T[];
    }
    return [];
};

export const truncate = (text?: string | null, limit = 100) =>
    text && text.length > limit ? `${text.slice(0, limit)}...` : (text ?? '');