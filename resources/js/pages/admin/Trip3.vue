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
import trips from '@/routes/admin/trips';
import { local as storageLocal } from '@/routes/storage';
import { ref, computed } from 'vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { Option } from '@lucide/vue';
import { Select } from '@/components/ui/select';


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'QL Trip Itinerary',
                href: trips.index(),
            },
        ],
    },
});

interface Trip {
    id: number;
    advisor: string;
    car_id: number;
    driver: string;
    day: string;
    day_at_format: string;
    origin: string;
    destination: string;
    departure_time: string;
    arrival_time: string;
    odo_start: number;
    odo_end: number;
    distance: number;
    is_confirm: number;
    overtime_total: number;
    overnight_total: number;
    toll_fee: number;
    airport_fee: number;
    holiday_total: number;
    note: string;
    created_at: string;
    
}
interface Car {
    id: number;
    license_plate: string;
    is_active: boolean;
}

interface Advisor {
    id: number;
    name: string;
}
interface Driver {
    id: number;
    name: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    trips: { data: Trip[]; links?: PaginationLink[] };
    cars: { data: Car[] };
    advisors: { data: Advisor[]};
    drivers: { data: Driver[]};
}

const props = defineProps<Props>();
const items = computed(() => props.trips.data ?? []);
const is_check = ref(false);

const editOpen = ref(false);
const deleteOpen = ref(false);
const createOpen = ref(false);
const selected = ref<Trip | null>(null);

const openEdit = (trip: Trip) => {
    selected.value = trip;
    editOpen.value = true;
};
const openDelete = (trip: Trip) => {
    selected.value = trip;
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

const formatDate = (date: string): string => {
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        // hour: '2-digit',
        // minute: '2-digit',
    }).format(new Date(date));
};

// console.log(props)
// console.log(props.cars)

</script>

<template>
  <!-- <pre>{{ cars }}</pre> -->
    <Head title="QL Trip Itinerary" />

    <Card class="m-4 overflow-hidden">
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Trip Itinerary</CardTitle>
          <Button @click="createOpen = true" size="sm">Create Trip Itinerary</Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
          <table class="w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-800">
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">ID</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Advisor</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Driver</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Origin</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Destination</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Day</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Distance</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Status</th>
                <th class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="trip in items" :key="trip.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ trip.id }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ trip.advisor }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ trip.driver }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ trip.origin }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ trip.destination }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ formatDate(trip.day) }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">{{ trip.distance }}</td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  {{ trip.is_confirm ? 'Confirmed' : 'Pending' }}
                </td>
                <td class="border border-gray-300 dark:border-gray-700 px-4 py-2">
                  <!-- Action buttons for edit and delete -->
                  <Button @click="openEdit(trip)" variant="outline" size="sm" class="mr-2">Edit</Button>
                  <Button @click="openDelete(trip)" variant="destructive" size="sm">Delete</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Links -->
        <div v-if="props.trips.links && props.trips.links.length > 0" class="mt-4 flex justify-center gap-2">
          <Link
            v-for="link in props.trips.links"
            :key="link.label"
            :href="link.url || trips.index().url"
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

    <!-- Create Trip Modal -->
    <Dialog v-model:open="createOpen">
      <DialogContent class="sm:max-w-[720px] flex flex-col">
        <DialogHeader>
          <DialogTitle>Create New Trip Itinerary</DialogTitle>
          <DialogDescription>
          </DialogDescription>
        </DialogHeader>
        <div class="overflow-y-auto max-h-[60vh] px-4">
          <Form
              v-bind="trips.store.form()"
              v-slot="{ errors, processing }"
              enctype="multipart/form-data" 
              @success="() => { createOpen = false;}"
              class="space-y-4"
          >
            <!-- Form fields for creating trip -->
            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="advisor">Advisor</Label>
                <select id="advisor" name="advisor" required class="border rounded p-2 w-full">
                  <option selected value="" disabled>-- Select Advisor --</option>
                  <option v-for="advisor in props.advisors" :key="advisor.id" :value="advisor.name">
                    {{ advisor.id }} - {{ advisor.name }}
                  </option>
                </select>
                <InputError :message="errors?.advisor" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="driver">Driver</Label>
                <select id="driver" name="driver" required class="border rounded p-2 w-full">
                  <option selected value="" disabled>-- Select driver --</option>
                  <option v-for="driver in props.drivers" :key="driver.id" :value="driver.name">
                    {{ driver.id }} - {{ driver.name }}
                  </option>
                </select>
                <InputError :message="errors?.driver" class="mt-2" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="day">Day</Label>
                <Input id="day" type="date" name="day" required />
                <InputError :message="errors?.day" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="car_id">Car ID</Label>
                <select id="car_id" name="car_id" required class="border rounded p-2 w-full">
                  <option selected value="" disabled>-- Select car --</option>
                  <option v-for="car in props.cars" :key="car.id" :value="car.id">
                    {{ car.id }} - {{ car.license_plate }}
                  </option>
                </select>
                <InputError :message="errors?.car_id" class="mt-2" />

              </div>
              
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="origin">Origin</Label>
                <Input id="origin" type="text" name="origin" required />
                <InputError :message="errors?.origin" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="destination">Destination</Label>
                <Input id="destination" type="text" name="destination" required />
                <InputError :message="errors?.destination" class="mt-2" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="departure_time">Departure Time</Label>
                <Input id="departure_time" type="time" name="departure_time" required />
                <InputError :message="errors?.departure_time" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="arrival_time">Arrival Time</Label>
                <Input id="arrival_time" type="time" name="arrival_time" required />
                <InputError :message="errors?.arrival_time" class="mt-2" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="odo_start">Odometer Start</Label>
                <Input id="odo_start" type="number" name="odo_start" required />
                <InputError :message="errors?.odo_start" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="odo_end">Odometer End</Label>
                <Input id="odo_end" type="number" name="odo_end" required />
                <InputError :message="errors?.odo_end" class="mt-2" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="overtime">Overtime</Label>
                <Input id="overtime" type="number" name="overtime" required />
                <InputError :message="errors?.overtime" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="overnight">Overnight</Label>
                <Input id="overnight" type="number" name="overnight" required />
                <InputError :message="errors?.overnight" class="mt-2" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label for="toll_fee">Toll fee</Label>
                <Input id="toll_fee" type="number" name="toll_fee" required />
                <InputError :message="errors?.toll_fee" class="mt-2" />
              </div>
              <div class="grid gap-2">
                <Label for="airport_fee">Airport/parking fee</Label>
                <Input id="airport_fee" type="number" name="airport_fee" required />
                <InputError :message="errors?.airport_fee" class="mt-2" />
              </div>
            </div>
            <div class="grid gap-2">
              <Label for="holiday">Holiday</Label>
              <Checkbox v-model="is_check"  type="checkbox"/>
              <Input v-if="is_check" type="number" id="holiday" name="holiday" placeholder="Holiday fee"/>
              <InputError :message="errors?.holiday" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="note">Note</Label>
              <Input id="note" type="text" name="note" />
              <InputError :message="errors?.note" class="mt-2" />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" @click="createOpen = false" :disabled="processing">Cancel</Button>
              <Button type="submit" :disabled="processing">Save Changes</Button>
            </DialogFooter>
          </Form>
        </div>
      </DialogContent>
    </Dialog>
    <!-- end Create Trip Modal -->

    <!-- Edit Trip Modal -->
    <Dialog v-model:open="editOpen">
      <DialogContent class="sm:max-w-[720px]">
        <DialogHeader>
          <DialogTitle>Edit Trip Itinerary</DialogTitle>
          <DialogDescription>
            Update the details of the trip itinerary below. Make sure to fill in all required fields before saving changes.
          </DialogDescription>
        </DialogHeader>
        <Form v-if="selected" v-bind="trips.update.form(selected.id)" 
          enctype="multipart/form-data" 
          @success="editOpen = false"
          v-slot="{ errors, processing }" class="space-y-4">
          <!-- Form fields for editing trip -->
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="advisor">Advisor</Label>
              <Input id="advisor" v-model="selected.advisor" type="text" name="advisor" required />
              <InputError :message="errors.advisor" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="driver">Driver</Label>
              <Input id="driver" v-model="selected.driver" type="text" name="driver" required />
              <InputError :message="errors.driver" class="mt-2" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="advisor">Advisor</Label>
              <Input id="advisor" v-model="selected.advisor" type="text" name="advisor" required />
              <InputError :message="errors.advisor" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="car_id">Car ID</Label>
              <Input id="car_id" v-model="selected.car_id" type="number" name="car_id" required />
              <InputError :message="errors.car_id" class="mt-2" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="day">Day</Label>
              <Input id="day" v-model="selected.day" type="date" name="day" required />
              <InputError :message="errors.day" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="origin">Origin</Label>
              <Input id="origin" v-model="selected.origin" type="text" name="origin" required />
              <InputError :message="errors.origin" class="mt-2" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="destination">Destination</Label>
              <Input id="destination" v-model="selected.destination" type="text" name="destination" required />
              <InputError :message="errors.destination" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="departure_time">Departure Time</Label>
              <Input id="departure_time" v-model="selected.departure_time" type="time" name="departure_time" required />
              <InputError :message="errors.departure_time" class="mt-2" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="arrival_time">Arrival Time</Label>
              <Input id="arrival_time" v-model="selected.arrival_time" type="time" name="arrival_time" required />
              <InputError :message="errors.arrival_time" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="odo_start">Odometer Start</Label>
              <Input id="odo_start" v-model="selected.odo_start" type="number" name="odo_start" required />
              <InputError :message="errors.odo_start" class="mt-2" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="odo_end">Odometer End</Label>
              <Input id="odo_end" v-model="selected.odo_end" type="number" name="odo_end" required />
              <InputError :message="errors.odo_end" class="mt-2" />
            </div>
            <div class="grid gap-2">
              <Label for="distance">Distance</Label>
              <Input id="distance" v-model="selected.distance" type="number" name="distance" required />
              <InputError :message="errors.distance" class="mt-2" />
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="note">Note</Label>
            <Input id="note" v-model="selected.note" type="text" name="note" />
            <InputError :message="errors.note" class="mt-2" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="editOpen = false" :disabled="processing">Cancel</Button>
            <Button type="submit" :disabled="processing">Save Changes</Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
    <!-- End Edit Trip Modal -->

    <!-- Delete Modal -->
    <Dialog v-model:open="deleteOpen">
      <DialogContent class="sm:max-w-[720px]">
        <DialogHeader>
          <DialogTitle>Delete Trip Itinerary</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this trip itinerary? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <Form v-if="selected" v-bind="trips.destroy.form(selected.id)" 
        enctype="multipart/form-data" reset-on-error
        @success="deleteOpen = false"
        v-slot="{ processing }" class="space-y-4">
            <p class="text-sm text-muted-foreground mb-4">
                This action will permanently delete the trip with ID: {{ selected.id }}, day: {{ formatDate(selected.day) }}, origin: {{ selected.origin }}, destination: {{ selected.destination }} .
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
