<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types/trip';

defineProps<{ links: PaginationLink[]; fallbackUrl: string }>();
</script>

<template>
    <div v-if="links.length" class="mt-4 flex justify-center gap-2">
        <Link v-for="(link, i) in links" :key="i"
              :href="link.url ?? fallbackUrl"
              class="rounded-md border border-gray-300 px-3 py-1 dark:border-gray-700"
              :class="[
                  link.active ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted/60',
                  !link.url ? 'pointer-events-none opacity-50' : '',
              ]">
            <span v-html="link.label" />
        </Link>
    </div>
</template>