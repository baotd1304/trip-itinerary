<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { 
        Dialog, DialogContent, DialogHeader, DialogTitle, 
        DialogDescription, DialogFooter, DialogTrigger 
      } from '@/components/ui/dialog';
import users from '@/routes/admin/users';
import { local as storageLocal } from '@/routes/storage';
import { ref, computed } from 'vue';


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'QL users',
                href: users.index(),
            },
        ],
    },
});

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    users: { data: User[]; links?: PaginationLink[] };
}

const props = defineProps<Props>();
// const breadcrumbs = ref<BreadcrumbItem[]>([
//     {
//         title: 'QL users',
//         href: '/admin/users',
//     },
// ]);
const items = computed(() => props.users.data ?? []);

const editOpen = ref(false);
const deleteOpen = ref(false);
const createOpen = ref(false);
const selected = ref<User | null>(null);
const newLocation = ref('');

const openEdit = (User: User) => {
    selected.value = User;
    editOpen.value = true;
};
const openDelete = (User: User) => {
    selected.value = User;
    deleteOpen.value = true;
};

const imageUrl = (path?: string | null) => {
    if (!path) return undefined;
    return storageLocal.url(path);
};

const decodeEtities = (str: string) => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
};

const formatEmbed = (input?: string | null) => {
    if (!input) return '';
    const decoded = decodeEtities(input);
    return decoded.replace(/width\s*=\s*["']\d+["']/gi, 'width="150"')
                  .replace(/height\s*=\s*["']\d+["']/gi, 'height="100"');
};

</script>

<template>
    <Head title="QL users" />

    <Card class="m-4 overflow-hidden">
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Users</CardTitle>
          <button @click="createOpen = true" size="sm">Create User</button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
          <table class="w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-800">
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">ID</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Name</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Email</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Created At</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in items" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ user.id }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ user.name }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ user.email }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ user.created_at }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  <!-- Action buttons for edit and delete -->
                  <Button @click="openEdit(user)" variant="outline" size="sm" class="mr-2">Edit</Button>
                  <Button @click="openDelete(user)" variant="destructive" size="sm">Delete</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Links -->
        <div v-if="props.users.links && props.users.links.length > 0" class="mt-4 flex justify-center gap-2">
          <Link
            v-for="link in props.users.links"
            :key="link.label"
            :href="link.url || users.index().url"
            class="px-3 py-1 rounded-md border border-gray-300 dark:border-gray-700"
            :class="[ 
              link.active ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted/60',
              !link.url ? 'pointer-events-none opacity-50' : ''
              ]"
          >
          <span v-html="link.label"></span>
          </Link>
        </div>
      </CardContent>
    </Card>

    <!-- Edit User Modal -->
    <Dialog v-model:open="editOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Edit User</DialogTitle>
          <DialogDescription>
            <!-- Edit user form -->
             Update the details of the user below. Make sure to fill in all required fields before saving changes.
          </DialogDescription>
        </DialogHeader>
        <Form v-if="selected" v-bind="users.update.form(selected.id)" 
        enctype="multipart/form-data" reset-on-error
        @success="editOpen = false"
        v-slot="{ errors, processing }" class="space-y-4">
          <!-- Form fields for editing user -->
          <div class="grip gap-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="selected.name" type="text" name="name" required />
            <InputError :message="errors.name" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="email">Email</Label>
            <Input id="email" v-model="selected.email" type="email" name="email" required />
            <InputError :message="errors.email" class="mt-2" />
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="editOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" :disabled="processing">Save Changes</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
    <!-- End Edit User Modal -->

    <!-- Create User Modal -->
    <Dialog v-model:open="createOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Create User</DialogTitle>
          <DialogDescription>
            <!-- Create user form -->
             Fill in the details of the new user below. Make sure to fill in all required fields before saving.
          </DialogDescription>
        </DialogHeader>
        <Form v-bind="users.store.form()" 
        enctype="multipart/form-data" reset-on-error
        @success="() => { createOpen = false; newLocation =''; }"
        v-slot="{ errors, processing }" class="space-y-4">
          <!-- Form fields for creating user -->
          <div class="grip gap-2">
            <Label for="new-name">Name</Label>
            <Input id="new-name" type="text" name="new-name" required />
            <InputError :message="errors?.name" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="new-email">Email</Label>
            <Input id="new-email" type="email" name="new-email" required />
            <InputError :message="errors?.email" class="mt-2" />
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="createOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" :disabled="processing">Save Changes</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>

</template>
