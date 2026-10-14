<script setup lang="ts">
import {
    EyeIcon, PencilIcon, Trash2Icon, LockIcon, UnlockIcon,
    MailCheckIcon, ShieldIcon, Loader2Icon,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { ADMIN_USER_TABLE_ACTIONS } from '@/lib/user-action-presets';
import type { AppUser, UserActionType } from '@/types/user';

const props = withDefaults(
    defineProps<{
        user: AppUser;
        only?: UserActionType[];
        processing?: boolean;
    }>(),
    {
        only: () => ADMIN_USER_TABLE_ACTIONS,
        processing: false,
    },
);

const emit = defineEmits<{
    (e: 'action', payload: { type: UserActionType; user: AppUser }): void;
}>();

const show = (type: UserActionType) => props.only.includes(type);
const isActive = () => props.user.is_active === true || props.user.is_active === 1;
const fire = (type: UserActionType) => emit('action', { type, user: props.user });
</script>

<template>
    <div class="flex items-center justify-center gap-1">
        <Tooltip v-if="show('view')">
            <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" :aria-label="$t('common.view')" @click.stop="fire('view')">
                    <EyeIcon class="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.view') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('edit') && user.can?.update">
            <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" :aria-label="$t('common.edit')" @click.stop="fire('edit')">
                    <PencilIcon class="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.edit') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('assignRole') && user.can?.assignRole">
            <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" :aria-label="$t('user.actions.assignRole')" @click.stop="fire('assignRole')">
                    <ShieldIcon class="h-4 w-4 text-indigo-600" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('user.actions.assignRole') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('toggleActive') && user.can?.toggleActive">
            <TooltipTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    :disabled="processing"
                    :aria-label="isActive() ? $t('user.actions.deactivate') : $t('user.actions.activate')"
                    @click.stop="fire('toggleActive')"
                >
                    <Loader2Icon v-if="processing" class="h-4 w-4 animate-spin" />
                    <LockIcon v-else-if="isActive()" class="h-4 w-4 text-amber-600" />
                    <UnlockIcon v-else class="h-4 w-4 text-emerald-600" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {{ isActive() ? $t('user.actions.deactivate') : $t('user.actions.activate') }}
            </TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('verifyEmail') && user.can?.verifyEmail && !user.email_verified_at">
            <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" :disabled="processing" :aria-label="$t('user.actions.verify')" @click.stop="fire('verifyEmail')">
                    <MailCheckIcon class="h-4 w-4 text-sky-600" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('user.actions.verify') }}</TooltipContent>
        </Tooltip>

        <Tooltip v-if="show('delete') && user.can?.delete">
            <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950" :aria-label="$t('common.delete')" @click.stop="fire('delete')">
                    <Trash2Icon class="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('common.delete') }}</TooltipContent>
        </Tooltip>
    </div>
</template>