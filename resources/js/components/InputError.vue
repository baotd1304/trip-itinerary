<script setup lang="ts">
import { useI18n } from 'vue-i18n';

defineProps<{
    message?: string;
}>();

const { t, te } = useI18n();
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-600 dark:text-red-500">
            {{ message && te(message) ? t(message) : message }}
        </p>
    </div>
</template>
