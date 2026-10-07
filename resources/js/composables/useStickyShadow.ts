import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

export function useStickyShadow(el: Ref<HTMLElement | null>) {
    const atStart = ref(true);
    const atEnd = ref(true);

    const update = () => {
        const node = el.value;
        if (!node) return;

        const max = node.scrollWidth - node.clientWidth;
        atStart.value = node.scrollLeft <= 1;
        atEnd.value = node.scrollLeft >= max - 1;
    };

    let observer: ResizeObserver | null = null;

    onMounted(() => {
        const node = el.value;
        if (!node) return;

        update();
        node.addEventListener('scroll', update, { passive: true });

        observer = new ResizeObserver(update);
        observer.observe(node);
    });

    onBeforeUnmount(() => {
        el.value?.removeEventListener('scroll', update);
        observer?.disconnect();
    });

    return { atStart, atEnd, update };
}