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
import cars from '@/routes/admin/cars';
import { local as storageLocal } from '@/routes/storage';
import { ref, computed } from 'vue';


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'QL Cars',
                href: cars.index(),
            },
        ],
    },
});

interface Car {
    id: number;
    name: string;
    brand: string;
    model: string;
    year: number;
    license_plate: string;
    owner: string;
    status: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    cars: { data: Car[]; links?: PaginationLink[] };
}

const props = defineProps<Props>();
// const breadcrumbs = ref<BreadcrumbItem[]>([
//     {
//         title: 'QL users',
//         href: '/admin/users',
//     },
// ]);
const items = computed(() => props.cars.data ?? []);

const editOpen = ref(false);
const deleteOpen = ref(false);
const createOpen = ref(false);
const selected = ref<Car | null>(null);
const newLocation = ref('');

const openEdit = (Car: Car) => {
    selected.value = Car;
    editOpen.value = true;
};
const openDelete = (Car: Car) => {
    selected.value = Car;
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
    <Head title="QL Cars" />

    <Card class="m-4 overflow-hidden">
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Cars</CardTitle>
          <Button @click="createOpen = true" size="sm">Create Car</Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
          <table class="w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-800">
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">ID</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Name</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Brand</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Model</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Year</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">License Plate</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Owner</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Status</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="car in items" :key="car.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.id }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.name }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.brand }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.model }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.year }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.license_plate }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ car.owner }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  {{ car.status ? 'Active' : 'Inactive' }}
                </td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  <!-- Action buttons for edit and delete -->
                  <Button @click="openEdit(car)" variant="outline" size="sm" class="mr-2">Edit</Button>
                  <Button @click="openDelete(car)" variant="destructive" size="sm">Delete</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Links -->
        <div v-if="props.cars.links && props.cars.links.length > 0" class="mt-4 flex justify-center gap-2">
          <Link
            v-for="link in props.cars.links"
            :key="link.label"
            :href="link.url || cars.index().url"
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

    <!-- Create Car Modal -->
    <Dialog v-model:open="createOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Create Car</DialogTitle>
          <DialogDescription>
            <!-- Create car form -->
             Fill in the details of the new car below. Make sure to fill in all required fields before saving.
          </DialogDescription>
        </DialogHeader>
        <Form
            v-bind="cars.store.form()"
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
            <Label for="brand">Brand</Label>
            <Input id="brand" type="text" name="brand" required />
            <InputError :message="errors?.brand" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="model">Model</Label>
            <Input id="model" type="text" name="model" required />
            <InputError :message="errors?.model" class="mt-2" />
          </div>
          <div class="grid gap-2">
            <Label for="year">Year</Label>
            <Input id="year" type="number" name="year" min="1900" required />
            <InputError :message="errors?.year" class="mt-2" />
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
        <Form v-if="selected" v-bind="cars.update.form(selected.id)" 
        enctype="multipart/form-data" reset-on-error
        @success="editOpen = false"
        v-slot="{ errors, processing }" class="space-y-4">
          <!-- Form fields for editing car -->
          <div class="grip gap-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="selected.name" type="text" name="name" required />
            <InputError :message="errors.name" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="brand">Brand</Label>
            <Input id="brand" v-model="selected.brand" type="text" name="brand" required />
            <InputError :message="errors.brand" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="model">Model</Label>
            <Input id="model" v-model="selected.model" type="text" name="model" required />
            <InputError :message="errors.model" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="year">Year</Label>
            <Input id="year" v-model="selected.year" type="number" name="year" min="1900" required />
            <InputError :message="errors.year" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="license_plate">License Plate</Label>
            <Input id="license_plate" v-model="selected.license_plate" type="text" name="license_plate" required />
            <InputError :message="errors.license_plate" class="mt-2" />
          </div>
          <div class="grip gap-2">
            <Label for="owner">Owner</Label>
            <Input id="owner" v-model="selected.owner" type="text" name="owner" required />
            <InputError :message="errors.owner" class="mt-2" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="editOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" :disabled="processing">Save Changes</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
    <!-- End Edit Car Modal -->

    <!-- Delete Modal -->
    <Dialog v-model:open="deleteOpen">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Delete Car</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this car? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <Form v-if="selected" v-bind="cars.destroy.form(selected.id)" 
        enctype="multipart/form-data" reset-on-error
        @success="deleteOpen = false"
        v-slot="{ processing }" class="space-y-4">
            <p class="text-sm text-muted-foreground mb-4">
                This action will permanently delete the car with ID: {{ selected.id }} and Name: {{ selected.name }}.
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
