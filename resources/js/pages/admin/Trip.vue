<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
  DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import trips from '@/routes/admin/trips';
import { ref, computed } from 'vue';

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
  id: number; advisor: string; car_id: number; driver: string;
  day: string; origin: string; destination: string;
  departure_time: string; arrival_time: string;
  odo_start: number; odo_end: number; distance: number;
  total_fee: number; is_confirm: number; note: string | null;
  trip_expense?: TripExpense | null, 
}
interface TripExpense {
  id:number, trip_id: number, overtime: number; toll_fee: number, airport_fee: number,
  is_overnight: boolean, is_holiday: boolean
}
interface Car { id: number; license_plate: string; is_active: boolean }
interface Advisor { id: number; name: string }
interface Driver { id: number; name: string }
interface PaginationLink { url: string | null; label: string; active: boolean }

const props = defineProps<{
  trips: { data: Trip[]; links?: PaginationLink[] };
  cars: { data: Car[] };
  advisors: { data: Advisor[] };
  drivers: { data: Driver[] };
  trip_expenses: { data: TripExpense[]}
}>();

/* Nhận: [...] | {data:[...]} | {data:{...}} | {0:{},1:{}} | null | undefined */
const toArray = <T,>(src: unknown): T[] => {
  if (!src) return [];
  if (Array.isArray(src)) return src as T[];
  if (typeof src === 'object') {
    const d = (src as any).data;
    if (Array.isArray(d)) return d as T[];
    if (d && typeof d === 'object') return Object.values(d) as T[];
    return Object.values(src as any).filter(
      (v) => v && typeof v === 'object',
    ) as T[];
  }
  return [];
};

const items    = computed(() => toArray<Trip>(props.trips));
const cars     = computed(() => toArray<Car>(props.cars));
const advisors = computed(() => toArray<Advisor>(props.advisors));
const drivers  = computed(() => toArray<Driver>(props.drivers));
const links    = computed(() => (props.trips as any)?.links ?? (props.trips as any)?.meta?.links ?? []);

const dialogOpen = ref(false);
const deleteOpen = ref(false);
const mode = ref<'create' | 'edit'>('create');
const is_overnight = ref(false);
const is_holiday   = ref(false);

/* ---------- State form (1 dialog cho cả create & edit) ---------- */
const emptyForm = () => ({
  id: 0, advisor: '', driver: '', car_id: '' as number | '',
  day: '', origin: '', destination: '',
  departure_time: '', arrival_time: '',
  odo_start: 0, odo_end: 0, overtime: 0, 
  toll_fee: 0, airport_fee: 0,
  is_overnight: false, is_holiday: false,
  note: '',
});

const model = ref(emptyForm());
const selected = ref<Trip | null>(null);

const openCreate = () => {
  mode.value = 'create';
  model.value = emptyForm();
  is_overnight.value = false;
  is_holiday.value = false;
  dialogOpen.value = true;
};

const openEdit = (trip: Trip) => {
  mode.value = 'edit';
  model.value = {
    ...emptyForm(),
    ...trip,
    car_id: Number(trip.car_id),
    day: toDateInput(trip.day),
    departure_time: toTimeInput(trip.departure_time),
    arrival_time: toTimeInput(trip.arrival_time),
    overtime: Number(trip.trip_expense?.overtime),
    toll_fee: Number(trip.trip_expense?.toll_fee),
    airport_fee: Number(trip.trip_expense?.airport_fee),
    is_overnight: Boolean(trip.trip_expense?.is_overnight),
    is_holiday: Boolean(trip.trip_expense?.is_holiday),
    note: trip.note ?? '',
  };
  dialogOpen.value = true;
};

const openDelete = (trip: Trip) => { selected.value = trip; deleteOpen.value = true; };

/* distance tính tự động */
const distance = computed(() =>
  Math.max(0, (Number(model.value.odo_end) || 0) - (Number(model.value.odo_start) || 0)),
);

const formProps = computed(() =>
  mode.value === 'create' ? trips.store.form() : trips.update.form(model.value.id),
);

/* ---------- Helpers ---------- */
const toDateInput = (v?: string | null) => (v ? String(v).slice(0, 10) : '');
const toTimeInput = (v?: string | null) => (v ? String(v).slice(0, 5) : '');

const formatDate = (date?: string | null) => {
  if (!date) return '—';
  const d = new Date(date);
  return Number.isNaN(d.getTime())
    ? '—'
    : new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d);
};
const formatVND = (value: number) => {
  return new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  }).format(value)
};

</script>

<template>
  <Head title="QL Trip Itinerary" />

  <Card class="overflow-hidden">
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Trip Itinerary</CardTitle>
        <Button size="sm" @click="openCreate">Create Trip Itinerary</Button>
      </div>
    </CardHeader>

    <CardContent>
      <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
        <table class="w-full table-auto border-collapse">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-800">
              <th v-for="h in ['ID','Advisor','Driver','Origin','Destination','Day','Distance',
              'Status', 'Overnight', 'Holiday', 'Total fee', 'Actions']"
                  :key="h" class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-left">
                {{ h }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!items.length">
              <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">Chưa có dữ liệu.</td>
            </tr>
            <tr v-for="trip in items" :key="trip.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.id }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.advisor }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.driver }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.origin }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.destination }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ formatDate(trip.day) }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.distance }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.is_confirm ? 'Confirmed' : 'Pending' }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.trip_expense?.is_overnight}}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.trip_expense?.is_holiday}}</td>
              <td class="border px-4 py-2 dark:border-gray-700 text-right">{{ formatVND(trip.total_fee)}}</td>
              <td class="border px-4 py-2 dark:border-gray-700 whitespace-nowrap">
                <Button variant="outline" size="sm" class="mr-2" @click="openEdit(trip)">Edit</Button>
                <Button variant="destructive" size="sm" @click="openDelete(trip)">Delete</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="links.length" class="mt-4 flex justify-center gap-2">
        <Link
          v-for="(link, i) in links" :key="i"
          :href="link.url ?? trips.index().url"
          class="rounded-md border border-gray-300 px-3 py-1 dark:border-gray-700"
          :class="[
            link.active ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted/60',
            !link.url ? 'pointer-events-none opacity-50' : '',
          ]"
        >
          <span v-html="link.label"/>
        </Link>
      </div>
    </CardContent>
  </Card>

  <!-- ===== Create / Edit (1 dialog duy nhất) ===== -->
  <Dialog v-model:open="dialogOpen">
    <DialogContent class="flex flex-col sm:max-w-[720px]">
      <DialogHeader>
        <DialogTitle>{{ mode === 'create' ? 'Create New Trip Itinerary' : 'Edit Trip Itinerary ID: ' + model.id + ' day: ' + formatDate(model.day) }}</DialogTitle>
      </DialogHeader>

      <div class="max-h-[65vh] overflow-y-auto px-1">
        <Form
          :key="mode + '-' + model.id"
          v-bind="formProps"
          v-slot="{ errors, processing }"
          class="space-y-4"
          @success="dialogOpen = false"
        >
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="advisor">Advisor</Label>
              <select id="advisor" v-model="model.advisor" name="advisor" required class="w-full rounded border p-2">
                <option selected value="" disabled>-- Select Advisor --</option>
                <option v-for="a in advisors" :key="a.id" :value="a.name">{{ a.id }} - {{ a.name }}</option>
              </select>
              <InputError :message="errors?.advisor" />
            </div>
            <div class="grid gap-2">
              <Label for="driver">Driver</Label>
              <select id="driver" v-model="model.driver" name="driver" required class="w-full rounded border p-2">
                <option value="" disabled>-- Select Driver --</option>
                <option v-for="d in drivers" :key="d.id" :value="d.name">{{ d.id }} - {{ d.name }}</option>
              </select>
              <InputError :message="errors?.driver" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="f-day">Day</Label>
              <Input id="f-day" v-model="model.day" type="date" name="day" required />
              <InputError :message="errors?.day" />
            </div>
            <div class="grid gap-2">
              <Label for="f-car">Car</Label>
              <select id="f-car" v-model="model.car_id" name="car_id" required class="w-full rounded border p-2">
                <option value="" disabled>-- Select Car --</option>
                <option v-for="c in cars" :key="c.id" :value="c.id">{{ c.id }} - {{ c.license_plate }}</option>
              </select>
              <InputError :message="errors?.car_id" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="f-origin">Origin</Label>
              <Input id="f-origin" v-model="model.origin" name="origin" required />
              <InputError :message="errors?.origin" />
            </div>
            <div class="grid gap-2">
              <Label for="f-destination">Destination</Label>
              <Input id="f-destination" v-model="model.destination" name="destination" required />
              <InputError :message="errors?.destination" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="f-dep">Departure Time</Label>
              <Input id="f-dep" v-model="model.departure_time" type="time" name="departure_time" required />
              <InputError :message="errors?.departure_time" />
            </div>
            <div class="grid gap-2">
              <Label for="f-arr">Arrival Time</Label>
              <Input id="f-arr" v-model="model.arrival_time" type="time" name="arrival_time" required />
              <InputError :message="errors?.arrival_time" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
              <Label for="f-odos">Odometer Start</Label>
              <Input id="f-odos" v-model.number="model.odo_start" type="number" min="0" name="odo_start" required />
              <InputError :message="errors?.odo_start" />
            </div>
            <div class="grid gap-2">
              <Label for="f-odoe">Odometer End</Label>
              <Input id="f-odoe" v-model.number="model.odo_end" type="number" :min="model.odo_start || 0"
                     name="odo_end" required />
              <InputError :message="errors?.odo_end" />
            </div>
            <div class="grid gap-2">
              <Label for="f-dist">Distance (auto)</Label>
              <Input id="f-dist" :model-value="distance" type="number" name="distance" readonly class="bg-muted" />
              <InputError :message="errors?.distance" />
            </div>
          </div>

          <!-- Overtime + phí -->
          <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
              <Label for="f-ot">Overtime</Label>
              <Input id="f-ot" v-model.number="model.overtime" type="number" min="0" max="4" name="overtime" />
              <InputError :message="errors?.overtime" />
            </div>
            <div class="grid gap-2">
              <Label for="f-toll">Toll fee</Label>
              <Input id="f-toll" v-model.number="model.toll_fee" type="number" min="0" name="toll_fee" />
              <InputError :message="errors?.toll_fee" />
            </div>
            <div class="grid gap-2">
              <Label for="f-air">Airport / Parking fee</Label>
              <Input id="f-air" v-model.number="model.airport_fee" type="number" min="0" name="airport_fee" />
              <InputError :message="errors?.airport_fee" />
            </div>
          </div>
          <!-- Overnight & Holiday: checkbox -->
          <div class="grid grid-cols-2 gap-4">
            <!-- Overnight -->
            <div class="grid gap-2 rounded-md border p-3">
              <div class="flex items-center gap-2">
                <Checkbox
                  id="f-overnight-check"
                  :model-value="model.is_overnight"
                  @update:model-value="(v: any) => (model.is_overnight = v === true)"
                />
                <Label for="f-overnight-check" class="cursor-pointer">Overnight (nghỉ đêm)</Label>
              </div>
              <input type="hidden" name="is_overnight" :value="model.is_overnight ? 1 : 0" />
              <InputError :message="errors?.is_overnight" />
            </div>

            <div class="grid gap-2 rounded-md border p-3">
              <div class="flex items-center gap-2">
                <Checkbox
                  id="f-holiday-check"
                  :model-value="model.is_holiday"
                  @update:model-value="(v: any) => (model.is_holiday = v === true)"
                />
                <Label for="f-holiday-check" class="cursor-pointer">Holiday (ngày lễ)</Label>
              </div>
              <input type="hidden" name="is_holiday" :value="model.is_holiday ? 1 : 0" />
              <InputError :message="errors?.is_holiday" />
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="f-note">Note</Label>
            <Input id="f-note" v-model="model.note" name="note" />
            <InputError :message="errors?.note" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" :disabled="processing" @click="dialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="processing">
              {{ mode === 'create' ? 'Create' : 'Save Changes' }}
            </Button>
          </DialogFooter>
        </Form>
      </div>
    </DialogContent>
  </Dialog>

  <!-- ===== Delete ===== -->
  <Dialog v-model:open="deleteOpen">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Delete Trip Itinerary</DialogTitle>
        <DialogDescription>Hành động này không thể hoàn tác.</DialogDescription>
      </DialogHeader>
      <Form v-if="selected" v-bind="trips.destroy.form(selected.id)" v-slot="{ processing }"
            class="space-y-4" @success="deleteOpen = false">
        <p class="mb-4 text-sm text-muted-foreground">
          Xoá vĩnh viễn chuyến ID <b>{{ selected.id }}</b> — ngày {{ formatDate(selected.day) }}, 
          {{ selected.origin }} → {{ selected.destination }}.
        </p>
        <DialogFooter>
          <Button type="button" variant="outline" :disabled="processing" @click="deleteOpen = false">Cancel</Button>
          <Button type="submit" variant="destructive" :disabled="processing">Delete</Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>