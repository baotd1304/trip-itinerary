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
import expenses from '@/routes/admin/expenses';
import { local as storageLocal } from '@/routes/storage';
import { ref, computed } from 'vue';


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'QL Expenses',
                href: expenses.index(),
            },
        ],
    },
});

interface Expense {
    id: number;
    overkm_rate: number;
    overtime_rate: number;
    overnight_rate: number;
    holiday_rate: number;
    notes: string;
    is_active: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    expenses: { data: Expense[]; links?: PaginationLink[] };
}

const props = defineProps<Props>();
// const breadcrumbs = ref<BreadcrumbItem[]>([
//     {
//         title: 'QL users',
//         href: '/admin/users',
//     },
// ]);
const items = computed(() => props.expenses.data ?? []);

const editOpen = ref(false);
const deleteOpen = ref(false);
const createOpen = ref(false);
const selected = ref<Expense | null>(null);
const newLocation = ref('');

const openEdit = (Expense: Expense) => {
    selected.value = Expense;
    editOpen.value = true;
};
const openDelete = (Expense: Expense) => {
    selected.value = Expense;
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
    <Head title="QL Expenses" />

    <Card class="m-4 overflow-hidden">
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Expenses</CardTitle>
          <Button @click="createOpen = true" size="sm">Create Expenses</Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
          <table class="w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-800">
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">ID</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Overkm</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Overtime</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Overnight</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Holiday</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Created at</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Active</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expense in items" :key="expense.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ expense.id }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ expense.overkm_rate }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ expense.overtime_rate }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ expense.overnight_rate }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ expense.holiday_rate }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ expense.created_at }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  {{ expense.is_active ? 'Active' : 'Inactive' }}
                </td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  <!-- Action buttons for edit and delete -->
                  <Button @click="openEdit(expense)" variant="outline" size="sm" class="mr-2">Edit</Button>
                  <Button @click="openDelete(expense)" variant="destructive" size="sm">Delete</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Links -->
        <div v-if="props.expenses.links && props.expenses.links.length > 0" class="mt-4 flex justify-center gap-2">
          <Link
            v-for="link in props.expenses.links"
            :key="link.label"
            :href="link.url || expenses.index().url"
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

    <!-- Create expense Modal -->
    <Dialog v-model:open="createOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Create Expense</DialogTitle>
          <DialogDescription>
            <!-- Create expense form -->
             Fill in the details of the new expense below. Make sure to fill in all required fields before saving.
          </DialogDescription>
        </DialogHeader>
        <Form
            v-bind="expenses.store.form()"
            v-slot="{ errors, processing }"
            enctype="multipart/form-data" reset-on-error
            @success="() => { createOpen = false;}"
            class="space-y-4"
        >
          <!-- Form fields for creating car -->
          <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" type="text" name="name" required />
            <InputError :message="errors?.name" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="overtime_rate">overtime_rate</Label>
            <Input id="overtime_rate" type="text" name="overtime_rate" required />
            <InputError :message="errors?.overtime_rate" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="model">Model</Label>
            <Input id="model" type="text" name="model" required />
            <InputError :message="errors?.model" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="holiday_rate">holiday_rate</Label>
            <Input id="holiday_rate" type="number" name="holiday_rate" min="1900" required />
            <InputError :message="errors?.holiday_rate" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="license_plate">License Plate</Label>
            <Input id="license_plate" type="text" name="license_plate" required />
            <InputError :message="errors?.license_plate" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="owner">Owner</Label>
            <Input id="owner" type="text" name="owner" required />
            <InputError :message="errors?.owner" class="mt-2" />
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" @click="createOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" :disabled="processing">Save Changes</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
    <!-- end Create Car Modal -->

    <!-- Edit Car Modal -->
    <Dialog v-model:open="editOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Edit Car</DialogTitle>
          <DialogDescription>
            <!-- Edit car form -->
             Update the details of the car below. Make sure to fill in all required fields before saving changes.
          </DialogDescription>
        </DialogHeader>
        <Form v-if="selected" v-bind="expenses.update.form(selected.id)" 
        enctype="multipart/form-data" reset-on-error
        @success="editOpen = false"
        v-slot="{ errors, processing }" class="space-y-4">
          <!-- Form fields for editing car -->
          <div class="grip gap-2">
            <Label for="overkm_rate">overkm_rate</Label>
            <Input id="overkm_rate" v-model="selected.overkm_rate" type="text" name="overkm_rate" required />
            <InputError :message="errors.overkm_rate" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="overtime_rate">overtime_rate</Label>
            <Input id="overtime_rate" v-model="selected.overtime_rate" type="text" name="overtime_rate" required />
            <InputError :message="errors.overtime_rate" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="overnight_rate">overnight_rate</Label>
            <Input id="overnight_rate" v-overnight_rate="selected.overnight_rate" type="text" name="overnight_rate" required />
            <InputError :message="errors.overnight_rate" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="holiday_rate">holiday_rate</Label>
            <Input id="holiday_rate" v-model="selected.holiday_rate" type="number" name="holiday_rate" min="1900" required />
            <InputError :message="errors.holiday_rate" class="mt-2" />
          </div>
         
          <div class="grip gap-2">
            <Label for="is_active">is_active</Label>
            <Input id="is_active" v-model="selected.is_active" type="text" name="is_active" required />
            <InputError :message="errors.is_active" class="mt-2" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="editOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" :disabled="processing">Save Changes</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
    <!-- End Edit expense Modal -->

    <!-- Delete Modal -->
    <Dialog v-model:open="deleteOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Delete expense</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this expense? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <Form v-if="selected" v-bind="expenses.destroy.form(selected.id)" 
        enctype="multipart/form-data" reset-on-error
        @success="deleteOpen = false"
        v-slot="{ processing }" class="space-y-4">
            <p class="text-sm text-muted-foreground mb-4">
                This action will permanently delete the expense with ID: {{ selected.id }} and overkm rate {{ selected.overkm_rate }}.
            </p>
          <DialogFooter>
            <Button type="button" variant="outline" @click="deleteOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" variant="destructive" :disabled="processing">Delete</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
    <!-- End Delete Modal -->

</template>
