<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { X } from 'lucide-vue-next';

export type TriState = '' | '1' | '0';

const overnight = defineModel<TriState>('overnight', { required: true });
const holiday = defineModel<TriState>('holiday', { required: true });

const activeCount = computed(
    () =>
        (overnight.value !== '' ? 1 : 0) +
        (holiday.value !== '' ? 1 : 0),
);

const clearGroup = () => {
    overnight.value = '';
    holiday.value = '';
};
</script>

<template>
    <div class="grid min-w-0 gap-1.5">
        <Label class="text-xs text-muted-foreground">
            {{ $t('trip.filters.surcharge') }}
        </Label>

        <div class="flex min-w-0 items-center gap-2">
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        type="button"
                        variant="outline"
                        class="h-9 min-w-0 flex-1 justify-between gap-2 font-normal"
                        :class="activeCount ? 'border-primary/60' : ''"
                    >
                        <span class="min-w-0 truncate">
                            {{ $t('trip.filters.surcharge') }}
                        </span>
                    </Button>
                </PopoverTrigger>

                <PopoverContent
                    class="space-y-3 w-40"
                    align="start"
                >
                    <!-- Nghỉ đêm -->
                    <div class="grid gap-1.5">
                        <Label
                            for="f-overnight"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('trip.filters.overnight') }}
                        </Label>

                        <select
                            id="f-overnight"
                            v-model="overnight"
                            class="h-9 w-full rounded-md border bg-background px-2 text-sm"
                        >
                            <option value="">
                                {{ $t('trip.filters.anyValue') }}
                            </option>
                            <option value="1">
                                {{ $t('trip.filters.optionYes') }}
                            </option>
                            <option value="0">
                                {{ $t('trip.filters.optionNo') }}
                            </option>
                        </select>
                    </div>

                    <!-- Ngày lễ -->
                    <div class="grid gap-1.5">
                        <Label
                            for="f-holiday"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('trip.filters.holiday') }}
                        </Label>

                        <select
                            id="f-holiday"
                            v-model="holiday"
                            class="h-9 w-full rounded-md border bg-background px-2 text-sm"
                        >
                            <option value="">
                                {{ $t('trip.filters.anyValue') }}
                            </option>
                            <option value="1">
                                {{ $t('trip.filters.optionYes') }}
                            </option>
                            <option value="0">
                                {{ $t('trip.filters.optionNo') }}
                            </option>
                        </select>
                    </div>
                </PopoverContent>
            </Popover>

            <Button
                v-if="activeCount"
                type="button"
                variant="outline"
                size="icon"
                class="h-9 w-9 shrink-0 relative"
                :title="$t('trip.filters.reset')"
                :aria-label="$t('trip.filters.reset')"
                @click="clearGroup"
            >
                <X class="h-4 w-4" />
                <Badge v-if="activeCount"
                    class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                    variant="destructive"
                >
                    {{ activeCount }}
                </Badge>
            </Button>
        </div>
    </div>
</template>