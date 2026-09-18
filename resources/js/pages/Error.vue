<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const props = defineProps<{ status: number; message?: string }>();

const title: Record<number, string> = {
  404: 'Không tìm thấy trang',
  403: 'Không có quyền truy cập',
  500: 'Lỗi hệ thống',
  503: 'Đang bảo trì',
};
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-4 px-6 text-center">
    <p class="text-6xl font-bold text-muted-foreground">{{ props.status }}</p>
    <h1 class="text-2xl font-semibold">{{ title[props.status] ?? 'Đã có lỗi xảy ra' }}</h1>
    <p class="max-w-md text-muted-foreground">{{ props.message }}</p>
    <Button as-child>
      <Link href="/">Về trang chủ</Link>
    </Button>
  </div>
</template>