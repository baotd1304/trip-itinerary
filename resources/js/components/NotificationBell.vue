<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    more as loadNotificationPage,
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

interface NotificationPage {
    data: AppNotification[];
    next_page: number | null;
}

const props = withDefaults(
    defineProps<{
        placement?: 'sidebar' | 'header';
    }>(),
    {
        placement: 'sidebar',
    },
);

const page = usePage();
const { t, locale } = useI18n();
const processingId = ref<string | null>(null);
const markingAll = ref(false);
const popoverOpen = ref(false);
const showingAll = ref(false);
const loadingAll = ref(false);
const loadError = ref<string | null>(null);
const nextPage = ref<number | null>(2);
const additionalNotifications = ref<AppNotification[]>([]);

const notifications = computed<AppNotification[]>(
    () => (page.props.notifications as AppNotification[] | undefined) ?? [],
);
const allNotifications = computed(() => {
    const notificationMap = new Map(
        notifications.value.map((notification) => [
            notification.id,
            notification,
        ]),
    );

    for (const notification of additionalNotifications.value) {
        notificationMap.set(notification.id, notification);
    }

    return [...notificationMap.values()];
});
const visibleNotifications = computed(() =>
    showingAll.value ? allNotifications.value : notifications.value,
);
const notificationCount = computed(() =>
    Number(page.props.notificationCount ?? notifications.value.length),
);
const unreadCount = computed(() =>
    Number(page.props.unreadNotificationCount ?? 0),
);
const badgeCount = computed(() =>
    unreadCount.value > 99 ? '99+' : String(unreadCount.value),
);

const isAppNotification = (value: unknown): value is AppNotification => {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const notification = value as Record<string, unknown>;

    return (
        typeof notification.id === 'string' &&
        typeof notification.type === 'string' &&
        typeof notification.data === 'object' &&
        notification.data !== null &&
        (typeof notification.read_at === 'string' ||
            notification.read_at === null) &&
        (typeof notification.created_at === 'string' ||
            notification.created_at === null)
    );
};

const isNotificationPage = (value: unknown): value is NotificationPage => {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const result = value as Record<string, unknown>;

    return (
        Array.isArray(result.data) &&
        result.data.every(isAppNotification) &&
        (typeof result.next_page === 'number' || result.next_page === null)
    );
};

const titleFor = (notification: AppNotification): string => {
    const key = notification.data.title_key;

    return key
        ? t(key, notification.data.title_params ?? {})
        : (notification.data.title ?? t('notifications.generic'));
};

const detailsFor = (notification: AppNotification): string | null =>
    notification.data.details ??
    notification.data.reason ??
    notification.data.note ??
    null;

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

const updateAdditionalReadAt = (readAt: string) => {
    additionalNotifications.value = additionalNotifications.value.map(
        (notification) => ({
            ...notification,
            read_at: readAt,
        }),
    );
};

const markAsRead = (notification: AppNotification) => {
    if (notification.read_at || processingId.value) {
        return;
    }

    processingId.value = notification.id;
    router.patch(
        markRead.url(notification.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                additionalNotifications.value =
                    additionalNotifications.value.map((item) =>
                        item.id === notification.id
                            ? { ...item, read_at: new Date().toISOString() }
                            : item,
                    );
            },
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
};

const markAllAsRead = () => {
    if (markingAll.value || unreadCount.value === 0) {
        return;
    }

    markingAll.value = true;
    router.patch(
        markAllRead.url(),
        {},
        {
            preserveScroll: true,
            onSuccess: () => updateAdditionalReadAt(new Date().toISOString()),
            onFinish: () => {
                markingAll.value = false;
            },
        },
    );
};

const toggleAllNotifications = async () => {
    if (showingAll.value) {
        showingAll.value = false;

        return;
    }

    showingAll.value = true;
    loadError.value = null;

    if (allNotifications.value.length >= notificationCount.value) {
        return;
    }

    loadingAll.value = true;

    try {
        while (
            nextPage.value !== null &&
            allNotifications.value.length < notificationCount.value
        ) {
            const response = await fetch(
                loadNotificationPage.url({ query: { page: nextPage.value } }),
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `Notification request failed with status ${response.status}`,
                );
            }

            const result: unknown = await response.json();

            if (!isNotificationPage(result)) {
                throw new Error('Notification response has an invalid shape');
            }

            additionalNotifications.value = [
                ...additionalNotifications.value,
                ...result.data.filter(
                    (notification) =>
                        !allNotifications.value.some(
                            (item) => item.id === notification.id,
                        ),
                ),
            ];
            nextPage.value = result.next_page;
        }
    } catch (error) {
        loadError.value = t('notifications.loadFailed');
        console.error('Unable to load all notifications.', error);
    } finally {
        loadingAll.value = false;
    }
};
</script>

<template>
    <Popover v-model:open="popoverOpen">
        <SidebarMenu v-if="props.placement === 'sidebar'">
            <SidebarMenuItem>
                <PopoverTrigger as-child>
                    <SidebarMenuButton
                        :tooltip="$t('notifications.title')"
                        :aria-label="$t('notifications.title')"
                        class="relative"
                    >
                        <Bell class="size-4" />
                        <span class="truncate">{{
                            $t('notifications.title')
                        }}</span>
                        <Badge
                            v-if="unreadCount"
                            variant="destructive"
                            class="ml-auto h-5 min-w-5 justify-center px-1 text-[10px]"
                        >
                            {{ badgeCount }}
                        </Badge>
                    </SidebarMenuButton>
                </PopoverTrigger>
            </SidebarMenuItem>
        </SidebarMenu>

        <PopoverTrigger v-else as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="relative"
                :aria-label="$t('notifications.title')"
            >
                <Bell class="size-5" />
                <Badge
                    v-if="unreadCount"
                    variant="destructive"
                    class="absolute -top-1 -right-1 h-5 min-w-5 justify-center border-2 border-background px-1 text-[10px]"
                >
                    {{ badgeCount }}
                </Badge>
                <span class="sr-only">{{ $t('notifications.title') }}</span>
            </Button>
        </PopoverTrigger>

        <PopoverContent
            :align="props.placement === 'sidebar' ? 'start' : 'end'"
            :side="props.placement === 'sidebar' ? 'right' : 'bottom'"
            :side-offset="8"
            class="w-[min(22rem,calc(100vw-2rem))] p-0"
        >
            <header
                class="flex items-center justify-between gap-3 border-b px-4 py-3"
            >
                <div class="min-w-0">
                    <p class="font-semibold">{{ $t('notifications.title') }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            $t('notifications.unreadCount', {
                                count: unreadCount,
                            })
                        }}
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

            <div
                v-if="visibleNotifications.length"
                class="max-h-[min(70vh,28rem)] overflow-y-auto"
            >
                <article
                    v-for="notification in visibleNotifications"
                    :key="notification.id"
                    class="flex gap-3 border-b px-3 py-3 last:border-b-0"
                    :class="
                        notification.read_at
                            ? 'bg-background'
                            : 'bg-primary/[0.04]'
                    "
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full"
                        :class="
                            notification.read_at
                                ? 'bg-transparent'
                                : 'bg-primary'
                        "
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1 space-y-1">
                        <button
                            v-if="internalUrl(notification.data.url)"
                            type="button"
                            class="block w-full text-left text-sm leading-snug font-medium hover:underline focus-visible:underline"
                            @click="
                                router.visit(
                                    internalUrl(notification.data.url)!,
                                )
                            "
                        >
                            {{ titleFor(notification) }}
                        </button>
                        <p v-else class="text-sm leading-snug font-medium">
                            {{ titleFor(notification) }}
                        </p>
                        <p
                            v-if="detailsFor(notification)"
                            class="line-clamp-2 text-xs text-muted-foreground"
                        >
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
                        :disabled="
                            processingId === notification.id || markingAll
                        "
                        @click="markAsRead(notification)"
                    >
                        <Check class="size-4" />
                    </Button>
                </article>
            </div>
            <p
                v-else
                class="px-4 py-10 text-center text-sm text-muted-foreground"
            >
                {{ $t('notifications.empty') }}
            </p>

            <p
                v-if="loadError"
                role="alert"
                class="border-t px-4 py-2 text-center text-xs text-destructive"
            >
                {{ loadError }}
            </p>

            <Button
                v-if="notificationCount > notifications.length || showingAll"
                type="button"
                variant="ghost"
                class="w-full justify-center gap-2 rounded-none border-t"
                :disabled="loadingAll"
                @click="toggleAllNotifications"
            >
                <span>
                    {{
                        loadingAll
                            ? $t('notifications.loadingAll')
                            : showingAll
                              ? $t('notifications.showRecent')
                              : $t('notifications.viewAll')
                    }}
                </span>
                <Badge variant="secondary">
                    {{ notificationCount }}
                </Badge>
            </Button>
        </PopoverContent>
    </Popover>
</template>
