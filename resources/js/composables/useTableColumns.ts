import { computed, shallowRef, watch } from 'vue';
interface ColumnDefinition<Key extends string> {
    key: Key;
    labelKey: string;
    required?: boolean;
}

interface Options<Key extends string> {
    columns: ColumnDefinition<Key>[];
    defaultColumns: Key[];
    storageKey: string;
    /** Số cột tuỳ chọn tối thiểu phải bật (tránh bảng trống nghĩa) */
    minOptional?: number;
}

export function useTableColumns<Key extends string>(options: Options<Key>) {
    const { columns, defaultColumns, storageKey, minOptional = 1 } = options;

    const validKeys = new Set<string>(columns.map((c) => c.key));
    const requiredKeys = columns.filter((c) => c.required).map((c) => c.key);
    const optionalColumns = columns.filter((c) => !c.required);
    const optionalKeys = optionalColumns.map((c) => c.key);

    /** Chuẩn hoá: loại key rác, luôn ép thêm cột required */
    const normalize = (input: unknown): Key[] => {
        const list = Array.isArray(input) ? input : [];
        const cleaned = list.filter(
            (key): key is Key => typeof key === 'string' && validKeys.has(key),
        );

        return Array.from(new Set<Key>([...cleaned, ...requiredKeys]));
    };

    /** Đọc localStorage NGAY khi khởi tạo → không nháy cột như onMounted */
    const readInitial = (): Key[] => {
        if (typeof window === 'undefined') {
return normalize(defaultColumns);
}

        try {
            const saved = window.localStorage.getItem(storageKey);

            if (!saved) {
return normalize(defaultColumns);
}

            const parsed = normalize(JSON.parse(saved));
            // Nếu dữ liệu cũ hỏng / rỗng → quay về mặc định
            const hasOptional = parsed.some((k) => optionalKeys.includes(k));

            return hasOptional ? parsed : normalize(defaultColumns);
        } catch {
            return normalize(defaultColumns);
        }
    };

    const visibleColumns = shallowRef<Key[]>(readInitial());

    /** Set để tra O(1) thay vì includes() O(n) trên mỗi cell */
    const visibleSet = computed(() => new Set(visibleColumns.value));
    const isVisible = (key: Key) => visibleSet.value.has(key);

    /** Cột thực sự render, GIỮ ĐÚNG thứ tự khai báo gốc */
    const renderedColumns = computed(() => columns.filter((c) => isVisible(c.key)));
    const visibleColumnCount = computed(() => renderedColumns.value.length);

    const selectedOptionalCount = computed(
        () => optionalKeys.filter((k) => isVisible(k)).length,
    );
    const allOptionalSelected = computed(
        () => optionalKeys.length > 0 && selectedOptionalCount.value === optionalKeys.length,
    );
    const someOptionalSelected = computed(
        () => selectedOptionalCount.value > 0 && !allOptionalSelected.value,
    );

    const toggle = (key: Key, checked: boolean) => {
        const column = columns.find((c) => c.key === key);

        if (column?.required) {
return;
}

        if (checked) {
            if (!isVisible(key)) {
visibleColumns.value = [...visibleColumns.value, key];
}

            return;
        }

        // Chặn tắt cột cuối cùng
        if (selectedOptionalCount.value <= minOptional) {
return;
}

        visibleColumns.value = visibleColumns.value.filter((k) => k !== key);
    };

    const toggleAll = () => {
        visibleColumns.value = allOptionalSelected.value
            ? normalize(optionalKeys.slice(0, minOptional))
            : normalize([...optionalKeys]);
    };

    const resetToDefault = () => {
        visibleColumns.value = normalize(defaultColumns);
    };

    /** Chỉ ghi localStorage ở đây — component không đụng vào */
    watch(
        visibleColumns,
        (value) => {
            if (typeof window === 'undefined') {
return;
}

            try {
                window.localStorage.setItem(storageKey, JSON.stringify(value));
            } catch {
                /* quota đầy / private mode: bỏ qua, không làm vỡ UI */
            }
        },
        { deep: true },
    );

    return {
        visibleColumns,
        renderedColumns,
        visibleColumnCount,
        optionalColumns,
        selectedOptionalCount,
        allOptionalSelected,
        someOptionalSelected,
        isVisible,
        toggle,
        toggleAll,
        resetToDefault,
    };
}