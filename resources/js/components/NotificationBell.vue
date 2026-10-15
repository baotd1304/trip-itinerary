<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    markAllRead,
    markRead,
} from '@/actions/App/Http/Controllers/NotificationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

interface AppNotification {
    id: string;
    type: string;
    data: {
        title?: string;
        title_key?: string;
        title_params?: Record<string, string | number>;
        details?: string | null;
        reason?: string | null;
        note?: string | null;
        url?: string;
    };
    read_at: string | null;
    created_at: string | null;
}

const page = usePage();
const { t, locale } = useI18n();
const processingId = ref<string | null>(null);
const markingAll = ref(false);

const notifications = computed<AppNotification[]>(
    () => (page.props.notifications as AppNotification[] | undefined) ?? [],
);
const unreadCount = computed(
    () => Number(page.props.unreadNotificationCount ?? 0),
);
const badgeCount = computed(() =>
    unreadCount.value > 99 ? '99+' : String(unreadCount.value),
);

const titleFor = (notification: AppNotification): string => {
    const key = notification.data.title_key;

    return key
        ? t(key, notification.data.title_params ?? {})
        : (notification.data.title ?? t('notifications.generic'));
};

const detailsFor = (notification: AppNotification): string | null =>
    notification.data.details ?? notification.data.reason ?? notification.data.note ?? null;

const internalUrl = (url?: string): string | null => {
    if (!url || typeof window === 'undefined') {
        return null;
    }

    try {
        const target = new URL(url, window.location.origin);

        return target.origin === window.location.origin
            ? `${target.pathname}${target.search}${target.hash}`
            : null;
    } catch {
        return null;
    }
};

const dateFor = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const dateLocale =
        locale.value === 'th'
            ? 'th-TH'
            : locale.value === 'en'
              ? 'en-US'
              : 'vi-VN';

    return new Intl.DateTimeFormat(dateLocale, {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(date);
};

const markAsRead = (notification: AppNotification) => {
    if (notification.read_at || processingId.value) {
        return;
    }

    processingId.value = notification.id;
    router.patch(markRead.url(notification.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
        },
    });
};

const markAllAsRead = () => {
    if (markingAll.value || unreadCount.value === 0) {
        return;
    }

    markingAll.value = true;
    router.patch(markAllRead.url(), {}, {
        preserveScroll: true,
        onFinish: () => {
            markingAll.value = false;
        },
    });
};
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <Popover>
                <PopoverTrigger as-child>
                    <SidebarMenuButton
                        :tooltip="$t('notifications.title')"
                        :aria-label="$t('notifications.title')"
                        class="relative"
                    >
                        <Bell class="size-4" />
                        <span class="truncate">{{ $t('notifications.title') }}</span>
                        <Badge
                            v-if="unreadCount"
                            variant="destructive"
                            class="ml-auto h-5 min-w-5 justify-center px-1 text-[10px]"
                        >
                            {{ badgeCount }}
                        </Badge>
                    </SidebarMenuButton>
                </PopoverTrigger>

                <PopoverContent align="start" side="right" :side-offset="8" class="w-[min(22rem,calc(100vw-2rem))] p-0">
                    <header class="flex items-center justify-between gap-3 border-b px-4 py-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $t('notifications.title') }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ $t('notifications.unreadCount', { count: unreadCount }) }}
                            </p>
                        </div>
                        <Button
                            v-if="unreadCount"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-8 shrink-0 gap-1.5 px-2 text-xs"
                            :disabled="markingAll"
                            @click="markAllAsRead"
                        >
                            <CheckCheck class="size-3.5" />
                            {{ $t('notifications.markAllRead') }}
                        </Button>
                    </header>

                    <div v-if="notifications.length" class="max-h-[min(70vh,28rem)] overflow-y-auto">
                        <article
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="flex gap-3 border-b px-3 py-3 last:border-b-0"
                            :class="notification.read_at ? 'bg-background' : 'bg-primary/[0.04]'"
                        >
                            <span
                                class="mt-1.5 size-2 shrink-0 rounded-full"
                                :class="notification.read_at ? 'bg-transparent' : 'bg-primary'"
                                aria-hidden="true"
                            />
                            <div class="min-w-0 flex-1 space-y-1">
                                <button
                                    v-if="internalUrl(notification.data.url)"
                                    type="button"
                                    class="block w-full text-left text-sm font-medium leading-snug hover:underline focus-visible:underline"
                                    @click="router.visit(internalUrl(notification.data.url)!)"
                                >
                                    {{ titleFor(notification) }}
                                </button>
                                <p v-else class="text-sm font-medium leading-snug">
                                    {{ titleFor(notification) }}
                                </p>
                                <p v-if="detailsFor(notification)" class="line-clamp-2 text-xs text-muted-foreground">
                                    {{ detailsFor(notification) }}
                                </p>
                                <time
                                    v-if="dateFor(notification.created_at)"
                                    class="block text-[11px] text-muted-foreground"
                                    :datetime="notification.created_at ?? undefined"
                                >
                                    {{ dateFor(notification.created_at) }}
                                </time>
                            </div>
                            <Button
                                v-if="!notification.read_at"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-8 shrink-0"
                                :aria-label="$t('notifications.markRead')"
                                :title="$t('notifications.markRead')"
                                :disabled="processingId === notification.id || markingAll"
                                @click="markAsRead(notification)"
                            >
                                <Check class="size-4" />
                            </Button>
                        </article>
                    </div>
                    <p v-else class="px-4 py-10 text-center text-sm text-muted-foreground">
                        {{ $t('notifications.empty') }}
                    </p>
                </PopoverContent>
            </Popover>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
