<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
  users: {
    data: Array<{
      id: number;
      name: string;
      email: string;
      created_at: string;
    }>;
    current_page: number;
    last_page: number;
    total: number;
  };
}>();
</script>

<template>
  <Head title="Quản lý user" />

  <div class="space-y-4 p-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Quản lý user</h1>
      <Link
        href="/admin/users"
        class="rounded bg-blue-600 px-4 py-2 text-white"
      >
        Refresh
      </Link>
    </div>

    <div class="overflow-hidden rounded-lg border bg-white shadow">
      <table class="min-w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Tên</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Ngày tạo</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users.data" :key="user.id" class="border-t">
            <td class="px-4 py-3">{{ user.id }}</td>
            <td class="px-4 py-3">{{ user.name }}</td>
            <td class="px-4 py-3">{{ user.email }}</td>
            <td class="px-4 py-3">{{ user.created_at }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>