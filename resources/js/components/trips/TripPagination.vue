<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
} from 'lucide-vue-next';
import type { PaginationLink } from '@/types/trip';

const props = defineProps<{
    links: PaginationLink[];
    fallbackUrl: string;
}>();

type PaginationItem =
    | {
          type: 'link';
          link: PaginationLink;
          index: number;
      }
    | {
          type: 'ellipsis';
          key: string;
      };

const cleanLabel = (label: string) =>
    label
        .replace(/<[^>]*>/g, '')
        .replace(/&laquo;|&lsaquo;|&raquo;|&rsaquo;/gi, '')
        .trim();

const isPrevious = (label: string) => {
    const value = cleanLabel(label).toLowerCase();

    return (
        value.includes('previous') ||
        value.includes('prev') ||
        value.includes('trước') ||
        value.includes('«') ||
        value.includes('‹')
    );
};

const isNext = (label: string) => {
    const value = cleanLabel(label).toLowerCase();

    return (
        value.includes('next') ||
        value.includes('sau') ||
        value.includes('»') ||
        value.includes('›')
    );
};

const pageNumber = (link: PaginationLink): number | null => {
    const value = cleanLabel(link.label);

    return /^\d+$/.test(value) ? Number(value) : null;
};

const visibleItems = computed<PaginationItem[]>(() => {
    const links = props.links;

    const previous = links
        .map((link, index) => ({ link, index }))
        .find(({ link }) => isPrevious(link.label));

    const next = links
        .map((link, index) => ({ link, index }))
        .find(({ link }) => isNext(link.label));

    const pages = links
        .map((link, index) => ({
            link,
            index,
            number: pageNumber(link),
        }))
        .filter(
            (
                item,
            ): item is {
                link: PaginationLink;
                index: number;
                number: number;
            } => item.number !== null,
        );

    // Không đủ trang để rút gọn
    if (pages.length <= 7) {
        return links.map((link, index) => ({
            type: 'link',
            link,
            index,
        }));
    }

    const currentIndex = pages.findIndex(({ link }) => link.active);

    const currentPage =
        currentIndex >= 0
            ? pages[currentIndex].number
            : pages[0].number;

    const firstPage = pages[0].number;
    const lastPage = pages[pages.length - 1].number;

    const pageSet = new Set<number>([
        firstPage,
        lastPage,
        currentPage - 2,
        currentPage - 1,
        currentPage,
        currentPage + 1,
        currentPage + 2,
    ]);

    const selectedPages = pages.filter(({ number }) =>
        pageSet.has(number),
    );

    const result: PaginationItem[] = [];

    if (previous) {
        result.push({
            type: 'link',
            link: previous.link,
            index: previous.index,
        });
    }

    let previousNumber: number | null = null;

    for (const item of selectedPages) {
        if (
            previousNumber !== null &&
            item.number - previousNumber > 1
        ) {
            result.push({
                type: 'ellipsis',
                key: `ellipsis-${previousNumber}-${item.number}`,
            });
        }

        result.push({
            type: 'link',
            link: item.link,
            index: item.index,
        });

        previousNumber = item.number;
    }

    if (next) {
        result.push({
            type: 'link',
            link: next.link,
            index: next.index,
        });
    }

    return result;
});
</script>

<template>
    <nav
        v-if="props.links.length"
        class="mt-6 flex justify-center"
        aria-label="Phân trang"
    >
        <div
            class="inline-flex items-center gap-1 rounded-lg border bg-background p-1 shadow-sm"
        >
            <template
                v-for="(item, index) in visibleItems"
                :key="item.type === 'ellipsis' ? item.key : `${item.index}-${index}`"
            >
                <!-- Dấu ... -->
                <span
                    v-if="item.type === 'ellipsis'"
                    class="flex h-9 w-9 items-center justify-center text-muted-foreground"
                    aria-hidden="true"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </span>

                <!-- Link phân trang -->
                <Link
                    v-else-if="item.link.url"
                    :href="item.link.url"
                    preserve-scroll
                    class="flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-ring"
                    :class="
                        item.link.active
                            ? 'bg-primary font-medium text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    :aria-current="
                        item.link.active ? 'page' : undefined
                ">
                    <template v-if="isPrevious(item.link.label)">
                        <ChevronLeft class="mr-1 h-4 w-4" />
                        <span class="hidden sm:inline"></span>
                    </template>

                    <template v-else-if="isNext(item.link.label)">
                        <span class="hidden sm:inline"></span>
                        <ChevronRight class="ml-1 h-4 w-4" />
                    </template>

                    <template v-else>
                        {{ cleanLabel(item.link.label) }}
                    </template>
                </Link>

                <!-- Nút disabled -->
                <span
                    v-else
                    class="flex h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-md px-3 text-sm text-muted-foreground/40"
                    aria-disabled="true"
                >
                    <template v-if="isPrevious(item.link.label)">
                        <ChevronLeft class="mr-1 h-4 w-4" />
                        <span class="hidden sm:inline">Trước</span>
                    </template>

                    <template v-else-if="isNext(item.link.label)">
                        <span class="hidden sm:inline">Sau</span>
                        <ChevronRight class="ml-1 h-4 w-4" />
                    </template>

                    <template v-else>
                        {{ cleanLabel(item.link.label) }}
                    </template>
                </span>
            </template>
        </div>
    </nav>
</template>