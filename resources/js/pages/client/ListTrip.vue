<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
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
import trips from '@/routes/client/trips';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge'
import { CircleCheckBigIcon, CircleX } from '@lucide/vue'
import { useClientCloudinaryUpload } from '@/composables/useClientCloudinaryUpload';
import { X, ImagePlus, Loader2 } from 'lucide-vue-next';
import { can } from '@/directives/can';

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
  id: number; advisor?: Advisor | null; car_id: number; driver?: Driver | null;
  day: string; origin: string; destination: string;
  departure_time: string; arrival_time: string;
  odo_start: number; odo_end: number; distance: number;
  total_fee: number; status: string; note: string | null;
  trip_expense?: TripExpense | null,
  images?: TripImage[];
  can?: TripPermissions;
  reopen_reason?: string | null;
  reopened_at?: string | null;
  reopener?: { id: number; name: string } | null;
}
interface TripPermissions { update: boolean; delete: boolean; reopen: boolean }
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
  can?: { create: boolean }
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

// check quyền user hiện tại
const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);
const auth = computed(() => usePage().props.auth);
/** User hiện tại có role "driver" không? */
const isDriver = computed(() => {
  const roles: string[] = authUser.value?.roles ?? [];
  return roles.includes('driver');
});

/** User hiện tại có role "advisor" không? */
const isAdvisor = computed(() => {
  const roles: string[] = authUser.value?.roles ?? [];
  return roles.includes('advisor');
});

const STATUS_LABEL: Record<string, string> = {
  pending: 'Pending',
  editting: 'Editting',
  confirmed: 'Confirmed',
  rejected: 'Rejected',
};

const STATUS_CLASS: Record<string, string> = {
  pending: 'bg-gray-500',
  editting: 'bg-amber-500',
  confirmed: 'bg-blue-500',
  rejected: 'bg-red-500',
};

const canUpdate = (trip: Trip) => trip.can?.update === true;
const canDelete = (trip: Trip) => trip.can?.delete === true;
const updateHint = (trip: Trip) =>
  canUpdate(trip)
    ? 'Chỉnh sửa chuyến'
    : trip.status === 'confirmed'
      ? 'Chuyến đã xác nhận — không thể chỉnh sửa'
      : 'Chỉ chỉnh sửa được khi trạng thái là "editting"';

const deleteHint = (trip: Trip) =>
  canDelete(trip)
    ? 'Xoá chuyến'
    : trip.status === 'confirmed'
      ? 'Chuyến đã xác nhận — không thể xoá'
      : 'Chỉ xoá được khi trạng thái là "pending"';


//state + handler reopen
const reopenOpen = ref(false);
const reopenTarget = ref<Trip | null>(null);

const canReopen = (trip: Trip) => trip.can?.reopen === true;

const openReopen = (trip: Trip) => {
  if (!canReopen(trip)) return;
  reopenTarget.value = trip;
  reopenOpen.value = true;
};


//Debug
console.log('Auth user:', auth.value?.user)
console.log('Roles:', auth.value?.user?.roles)       // ['driver'] hoặc ['advisor']
console.log('Permissions:', auth.value?.user?.permissions)
console.log('Can:', auth.value?.user?.can)

/* ---------- State form (1 dialog cho cả create & edit) ---------- */
const emptyForm = () => ({
  id: 0, advisor_id: '' as number | '', driver_id: '' as number | '', car_id: '' as number | '',
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
  // Tự động set advisor/driver khi mở dialog
  // Nếu user là driver → auto set driver_id = user.id
  if (isDriver.value && authUser.value?.id) {
    model.value.driver_id = authUser.value.id;
  }
  // Nếu user là advisor → auto set advisor_id = user.id
  if (isAdvisor.value && authUser.value?.id) {
    model.value.advisor_id = authUser.value.id;
  }
  is_overnight.value = false;
  is_holiday.value = false;
  resetUploads();
  existingImages.value = [];
  dialogOpen.value = true;
};

const openEdit = (trip: Trip) => {
  if (!canUpdate(trip)) {
    alert('Bạn không có quyền chỉnh sửa chuyến này.');
    return;
  }
  mode.value = 'edit';
  model.value = {
    ...emptyForm(),
    ...trip,
    car_id: Number(trip.car_id),
    day: toDateInput(trip.day),
    departure_time: toTimeInput(trip.departure_time),
    arrival_time: toTimeInput(trip.arrival_time),
    overtime: Number(trip.trip_expense?.overtime ?? 0),
    toll_fee: Number(trip.trip_expense?.toll_fee ?? 0),
    airport_fee: Number(trip.trip_expense?.airport_fee ?? 0),
    is_overnight: Boolean(trip.trip_expense?.is_overnight),
    is_holiday: Boolean(trip.trip_expense?.is_holiday),
    note: trip.note ?? '',
  };
  resetUploads();
  existingImages.value = [...(trip.images ?? [])];
  dialogOpen.value = true;
};

const openDelete = (trip: Trip) => {
  if (!canDelete(trip)) {
    alert('Bạn không có quyền xoá chuyến này.');
    return;
  }
  selected.value = trip; 
  deleteOpen.value = true; 
};

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

/* ===== Upload ảnh Cloudinary ===== */
const MAX_IMAGES = 10;

interface TripImage { id: number; url: string; public_id: string }

const {
  items: uploadItems,
  uploading: isUploading,
  uploaded: uploadedImages,
  addFiles,
  remove: removeUpload,
  reset: resetUploads,
  discardOrphans,
} = useClientCloudinaryUpload();

const existingImages = ref<TripImage[]>([]);
const previewImage = ref<string | null>(null);

const totalImages = computed(() => existingImages.value.length + uploadItems.value.length);
const remainingSlots = computed(() => Math.max(0, MAX_IMAGES - totalImages.value));

const onPickFiles = async (e: Event) => {
  const input = e.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  input.value = '';
  if (files.length) await addFiles(files, remainingSlots.value);
};

/** Chỉ cần bỏ khỏi danh sách giữ lại; server sẽ tự xoá phần còn thiếu */
const removeExisting = (img: TripImage) => {
  existingImages.value = existingImages.value.filter((i) => i.id !== img.id);
};

const closeDialog = async (discard = false) => {
  if (discard) await discardOrphans();
  else resetUploads();
  existingImages.value = [];
  dialogOpen.value = false;
};

const thumb = (url: string) =>
  url.replace('/upload/', '/upload/c_fill,w_240,h_240,q_auto,f_auto/');

</script>

<template>
  <Head title="QL Trip Itinerary" />

  <Card class="overflow-hidden">
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Trip Itinerary</CardTitle>
        <!-- <Button v-can="'driver'" size="sm" @click="openCreate">Create Trip Itinerary</Button> -->
        <!-- Nút tạo mới: dựa trên quyền từ server -->
        <Button v-if="props.can?.create" size="sm" @click="openCreate">Create Trip Itinerary</Button>
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
              <td colspan="12" class="px-4 py-8 text-center text-muted-foreground">Chưa có dữ liệu.</td>
            </tr>
            <tr v-for="trip in items" :key="trip.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.id }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.advisor?.name }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.driver?.name }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.origin }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.destination }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ formatDate(trip.day) }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">{{ trip.distance }}</td>
              <td class="border px-4 py-2 dark:border-gray-700">
                <!-- <Badge class="inline-flex min-w-[80px] justify-center text-white"
                  :class="{ pending: 'bg-gray-500', confirmed: 'bg-blue-500', rejected: 'bg-red-500' }[trip.status]">
                  {{ { pending: 'Pending', confirmed: 'Confirmed', rejected: 'Rejected' }[trip.status] }}
                </Badge> -->
                <Badge class="inline-flex min-w-[80px] justify-center text-white"
                      :class="STATUS_CLASS[trip.status] ?? 'bg-gray-500'">
                  {{ STATUS_LABEL[trip.status] ?? trip.status }}
                </Badge>
                <p v-if="trip.status === 'editting' && trip.reopen_reason"
                  class="mt-1 max-w-[200px] text-xs text-amber-600 dark:text-amber-400"
                  :title="trip.reopen_reason">
                  {{ trip.reopener?.name ?? 'Quản trị' }}: {{ trip.reopen_reason }}
                </p>
              </td>
              <td class="border px-4 py-2 dark:border-gray-700 ">
                <div class="flex items-center justify-center">
                  <CircleCheckBigIcon v-if="trip.trip_expense?.is_overnight" class="text-green-500"> </CircleCheckBigIcon>
                  <CircleX v-else class="text-gray-500"> </CircleX>
                </div>
              </td>
              <td class="border px-4 py-2 dark:border-gray-700">
                <div class="flex items-center justify-center">
                  <CircleCheckBigIcon v-if="trip.trip_expense?.is_holiday" class="text-green-500"> </CircleCheckBigIcon>
                  <CircleX v-else class="text-gray-500"> </CircleX>
                </div>
              </td>
              <td class="border px-4 py-2 dark:border-gray-700 text-right">{{ formatVND(trip.total_fee)}}</td>
              <!-- <td class="border px-4 py-2 dark:border-gray-700 whitespace-nowrap">
                <Button variant="outline" size="sm" class="mr-2" @click="openEdit(trip)">Edit</Button>
                <Button variant="destructive" size="sm" @click="openDelete(trip)">Delete</Button>
                <Button
                  variant="outline" size="sm" class="mr-2"
                  :disabled="!canUpdate(trip)"
                  :title="updateHint(trip)"
                  @click="openEdit(trip)"
                >
                  Edit
                </Button>

                <Button
                  variant="destructive" size="sm"
                  :disabled="!canDelete(trip)"
                  :title="deleteHint(trip)"
                  @click="openDelete(trip)"
                >
                  Delete
                </Button>
              </td> -->
              <td class="border px-4 py-2 dark:border-gray-700 whitespace-nowrap">
                <Button variant="outline" size="sm" class="mr-2"
                        :disabled="!canUpdate(trip)" :title="updateHint(trip)"
                        @click="openEdit(trip)">
                  Edit
                </Button>

                <Button v-if="canReopen(trip)" variant="secondary" size="sm" class="mr-2"
                        title="Mở khoá để tài xế chỉnh sửa lại"
                        @click="openReopen(trip)">
                  Reopen
                </Button>

                <Button variant="destructive" size="sm"
                        :disabled="!canDelete(trip)" :title="deleteHint(trip)"
                        @click="openDelete(trip)">
                  Delete
                </Button>
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
          @success="closeDialog(false)"
        >
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="advisor_id">Advisor</Label>
              <select id="advisor_id" v-model="model.advisor_id" name="advisor_id" required class="w-full rounded border p-2">
                <option selected value="" disabled>-- Select Advisor --</option>
                <option v-for="a in advisors" :key="a.id" :value="a.id">{{ a.id }} - {{ a.name }}</option>
              </select>
              <InputError :message="errors?.advisor_id" />
            </div>
            <!-- Driver -->
            <div class="grid gap-2">
              <Label for="driver_id">Driver</Label>
              <!-- Nếu là driver: hiện text + input hidden -->
              <div v-if="isDriver" class="flex items-center rounded border bg-gray-100 p-2 text-sm">
                {{ authUser?.id }} - {{ authUser?.name }}
                <input type="hidden" name="driver_id" :value="authUser?.id" />
              </div>
              <!-- Không phải driver: hiện select -->
              <select v-else
                id="driver_id" v-model="model.driver_id" name="driver_id" required 
                class="w-full rounded border p-2">
                <option value="" disabled>-- Select Driver --</option>
                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.id }} - {{ d.name }}</option>
              </select>
              <InputError :message="errors?.driver_id" />
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

          <!-- ===== Hình ảnh chuyến đi ===== -->
          <div class="grid gap-3 rounded-md border p-3">
            <div class="flex items-center justify-between">
              <Label class="font-medium">Hình ảnh chuyến đi</Label>
              <span class="text-xs text-muted-foreground">{{ totalImages }}/{{ MAX_IMAGES }} ảnh</span>
            </div>

            <!-- Vùng chọn file -->
            <label
              class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed
                    px-4 py-6 text-sm text-muted-foreground transition hover:bg-muted/50"
              :class="remainingSlots === 0 ? 'pointer-events-none opacity-50' : ''"
            >
              <ImagePlus class="h-5 w-5" />
              <span>Nhấn để chọn ảnh (JPG, PNG, WEBP · tối đa 5MB/ảnh)</span>
              <input
                type="file"
                class="hidden"
                accept="image/jpeg,image/png,image/webp,image/heic"
                multiple
                :disabled="remainingSlots === 0"
                @change="onPickFiles"
              />
            </label>

            <!-- Lưới ảnh -->
            <div v-if="totalImages" class="grid grid-cols-4 gap-3 sm:grid-cols-5">
              <!-- Ảnh đã lưu (chế độ edit) -->
              <div
                v-for="img in existingImages"
                :key="'old-' + img.id"
                class="group relative aspect-square overflow-hidden rounded-md border"
              >
                <img :src="thumb(img.url)" :alt="'Ảnh ' + img.id"
                    class="h-full w-full cursor-zoom-in object-cover"
                    @click="previewImage = img.url" />
                <button type="button"
                        class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0
                              transition group-hover:opacity-100"
                        @click="removeExisting(img)">
                  <X class="h-3.5 w-3.5" />
                </button>
              </div>

              <!-- Ảnh mới upload -->
              <div
                v-for="item in uploadItems"
                :key="item.uid"
                class="group relative aspect-square overflow-hidden rounded-md border"
                :class="item.status === 'error' ? 'border-red-500' : ''"
              >
                <img :src="item.result ? thumb(item.result.url) : item.preview" :alt="item.name"
                    class="h-full w-full object-cover"
                    :class="item.status !== 'done' ? 'opacity-50' : 'cursor-zoom-in'"
                    @click="item.result && (previewImage = item.result.url)" />

                <div v-if="item.status === 'uploading'"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/40 text-white">
                  <Loader2 class="h-4 w-4 animate-spin" />
                  <span class="text-xs font-medium">{{ item.progress }}%</span>
                </div>

                <div v-if="item.status === 'error'"
                    class="absolute inset-x-0 bottom-0 bg-red-600/90 px-1 py-0.5 text-[10px] leading-tight text-white">
                  {{ item.error }}
                </div>

                <button type="button"
                        class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0
                              transition group-hover:opacity-100"
                        @click="removeUpload(item.uid)">
                  <X class="h-3.5 w-3.5" />
                </button>
              </div>

              <!-- Ảnh mới vừa upload lên Cloudinary -->
              <template v-for="(img, i) in uploadedImages" :key="'up-' + img.public_id">
                <input type="hidden" :name="`images[${i}][public_id]`" :value="img.public_id" />
                <input type="hidden" :name="`images[${i}][url]`"       :value="img.url" />
                <input type="hidden" :name="`images[${i}][format]`"    :value="img.format ?? ''" />
                <input type="hidden" :name="`images[${i}][width]`"     :value="img.width ?? ''" />
                <input type="hidden" :name="`images[${i}][height]`"    :value="img.height ?? ''" />
                <input type="hidden" :name="`images[${i}][bytes]`"     :value="img.bytes ?? ''" />
              </template>

              <!-- Cờ báo form có quản lý ảnh: BẮT BUỘC, để server biết được ý định "xoá hết" -->
              <input type="hidden" name="images_synced" value="1" />
              <!-- Danh sách ảnh cũ được GIỮ LẠI; rỗng = xoá tất cả -->
              <input v-for="img in existingImages" :key="'keep-' + img.id"
                type="hidden" name="kept_image_ids[]" :value="img.id"/>
              <InputError :message="errors?.images" />
            </div>
            <input type="hidden" name="images_synced" value="1" />
            <input v-for="img in existingImages" :key="'keep-' + img.id"
                  type="hidden" name="kept_image_ids[]" :value="img.id" />
            <InputError :message="errors?.images" />
          </div>

          <div class="grid gap-2">
            <Label for="f-note">Note</Label>
            <Input id="f-note" v-model="model.note" name="note" />
            <InputError :message="errors?.note" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" :disabled="processing" @click="closeDialog(false)">
              Cancel
            </Button>
            <Button type="submit" :disabled="processing || isUploading">
              <Loader2 v-if="isUploading" class="mr-2 h-4 w-4 animate-spin" />
              {{ isUploading ? 'Đang tải ảnh...' : (mode === 'create' ? 'Create' : 'Save Changes') }}
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

  <!-- Lightbox xem anh -->
  <Dialog :open="!!previewImage" @update:open="(v) => !v && (previewImage = null)">
    <DialogContent class="sm:max-w-[860px]">
      <DialogHeader>
        <DialogTitle>Xem ảnh</DialogTitle>
      </DialogHeader>
      <img v-if="previewImage" :src="previewImage" alt="Ảnh chuyến đi"
          class="max-h-[70vh] w-full rounded-md object-contain" />
    </DialogContent>
  </Dialog>


  <!-- Diablog Reopen -->
  <Dialog v-model:open="reopenOpen">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Mở khoá chỉnh sửa chuyến</DialogTitle>
        <DialogDescription>
          Chuyến sẽ chuyển sang trạng thái <b>editting</b> và tài xế có thể cập nhật lại.
        </DialogDescription>
      </DialogHeader>

      <Form v-if="reopenTarget"
            v-bind="trips.reopen.form(reopenTarget.id)"
            v-slot="{ errors, processing }"
            class="space-y-4"
            @success="reopenOpen = false">
        <p class="text-sm text-muted-foreground">
          Chuyến ID <b>{{ reopenTarget.id }}</b> — {{ formatDate(reopenTarget.day) }},
          {{ reopenTarget.origin }} → {{ reopenTarget.destination }}
        </p>

        <div class="grid gap-2">
          <Label for="reopen_reason">Lý do yêu cầu sửa lại <span class="text-red-500">*</span></Label>
          <Textarea id="reopen_reason" name="reopen_reason" rows="3" required
                    placeholder="VD: Sai số odo kết thúc, thiếu ảnh hoá đơn phí cầu đường..." />
          <InputError :message="errors?.reopen_reason" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" :disabled="processing" @click="reopenOpen = false">
            Cancel
          </Button>
          <Button type="submit" :disabled="processing">Xác nhận mở khoá</Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>


</template>