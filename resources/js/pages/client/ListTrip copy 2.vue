<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
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
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { CircleCheckBigIcon, CircleX } from '@lucide/vue';
import { useClientCloudinaryUpload } from '@/composables/useClientCloudinaryUpload';
import {
  X, ImagePlus, Loader2, Eye, Pencil, Trash2,
  Unlock, Check, Ban, ThumbsUp, ThumbsDown,
} from 'lucide-vue-next';

defineOptions({
  layout: {
    breadcrumbs: [{ title: 'QL Trip Itinerary', href: trips.index() }],
  },
});

/* ==================== Types ==================== */
type TripStatus = 'pending' | 'editing' | 'confirmed' | 'rejected';
type ReopenStatus = 'pending' | 'approved' | 'rejected';
type DialogMode = 'create' | 'edit' | 'view';

interface PersonRef { id: number; name: string }
interface Car { id: number; license_plate: string; is_active: boolean }
interface Advisor { id: number; name: string }
interface Driver { id: number; name: string }
interface TripImage { id: number; url: string; public_id: string }
interface PaginationLink { url: string | null; label: string; active: boolean }

interface TripExpense {
  id: number; trip_id: number; overtime: number;
  toll_fee: number; airport_fee: number;
  is_overnight: boolean; is_holiday: boolean;
  overtime_rate?: number; overnight_rate?: number; holiday_rate?: number;
}

interface ReopenRequest {
  id: number;
  reason: string;
  status: ReopenStatus;
  review_note: string | null;
  reviewed_at?: string | null;
  requester?: PersonRef | null;
  reviewer?: PersonRef | null;
  created_at: string;
}

interface TripPermissions {
  update: boolean; delete: boolean;
  requestReopen: boolean; review: boolean; reviewReopen: boolean;
}

interface Trip {
  id: number; advisor?: Advisor | null; car_id: number; driver?: Driver | null;
  day: string; origin: string; destination: string;
  departure_time: string; arrival_time: string;
  odo_start: number; odo_end: number; distance: number;
  total_fee: number; note: string | null;
  trip_expense?: TripExpense | null;
  images?: TripImage[];
  can?: TripPermissions;
  status: TripStatus;
  reject_reason?: string | null;
  reviewer?: PersonRef | null;
  pending_reopen_request?: ReopenRequest | null;
  latest_reopen_request?: ReopenRequest | null;
}

const props = defineProps<{
  trips: { data: Trip[]; links?: PaginationLink[] };
  cars: { data: Car[] };
  advisors: { data: Advisor[] };
  drivers: { data: Driver[] };
  trip_expenses?: { data: TripExpense[] };
  can?: { create: boolean };
}>();

/* ==================== Normalize props ==================== */
const toArray = <T,>(src: unknown): T[] => {
  if (!src) return [];
  if (Array.isArray(src)) return src as T[];
  if (typeof src === 'object') {
    const d = (src as any).data;
    if (Array.isArray(d)) return d as T[];
    if (d && typeof d === 'object') return Object.values(d) as T[];
    return Object.values(src as any).filter((v) => v && typeof v === 'object') as T[];
  }
  return [];
};

const items    = computed(() => toArray<Trip>(props.trips));
const cars     = computed(() => toArray<Car>(props.cars));
const advisors = computed(() => toArray<Advisor>(props.advisors));
const drivers  = computed(() => toArray<Driver>(props.drivers));
const links    = computed(() => (props.trips as any)?.links ?? (props.trips as any)?.meta?.links ?? []);

/* ==================== Auth ==================== */
const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);

const isDriver  = computed(() => ((authUser.value?.roles ?? []) as string[]).includes('driver'));
const isAdvisor = computed(() => ((authUser.value?.roles ?? []) as string[]).includes('advisor'));

/* ==================== Status helpers ==================== */
const STATUS_LABEL: Record<TripStatus, string> = {
  pending:   'Chờ duyệt',
  editing:   'Đang sửa',
  confirmed: 'Đã xác nhận',
  rejected:  'Bị từ chối',
};

const STATUS_CLASS: Record<TripStatus, string> = {
  pending:   'bg-amber-500',
  editing:   'bg-indigo-500',
  confirmed: 'bg-emerald-600',
  rejected:  'bg-red-500',
};

/* ==================== Permission helpers ==================== */
const canUpdate        = (t?: Trip | null) => t?.can?.update === true;
const canDelete        = (t?: Trip | null) => t?.can?.delete === true;
const canRequestReopen = (t?: Trip | null) => t?.can?.requestReopen === true;
const canReview        = (t?: Trip | null) => t?.can?.review === true;
const canReviewReopen  = (t?: Trip | null) => t?.can?.reviewReopen === true && !!t?.pending_reopen_request;

const updateHint = (t: Trip) => {
  if (!canUpdate(t)) {
    return t.status === 'confirmed'
      ? 'Chuyến đã xác nhận — hãy gửi yêu cầu mở khoá để chỉnh sửa'
      : 'Không thể chỉnh sửa ở trạng thái hiện tại';
  }
  switch (t.status) {
    case 'pending':  return 'Chỉnh sửa — chuyến vẫn giữ trạng thái "Chờ duyệt"';
    case 'editing':  return 'Chỉnh sửa — sau khi lưu sẽ chuyển sang "Chờ duyệt"';
    case 'rejected': return 'Sửa lại chuyến bị từ chối — sau khi lưu sẽ gửi duyệt lại';
    default:         return 'Chỉnh sửa chuyến';
  }
};

const deleteHint = (t: Trip) =>
  canDelete(t) ? 'Xoá chuyến đi' : 'Chỉ xoá được khi chuyến ở trạng thái "Chờ duyệt"';

const editButtonLabel = (t?: Trip | null) =>
  t?.status === 'rejected' ? 'Sửa & gửi lại' : 'Sửa';

/* ==================== Reopen helpers ==================== */
const latestReopen   = (t?: Trip | null) => t?.latest_reopen_request ?? null;
const approvedReopen = (t?: Trip | null) => (latestReopen(t)?.status === 'approved' ? latestReopen(t) : null);
const rejectedReopen = (t?: Trip | null) => (latestReopen(t)?.status === 'rejected' ? latestReopen(t) : null);

const editingNote = (t: Trip) => {
  if (t.status !== 'editing') return null;
  const r = approvedReopen(t);
  if (!r) return null;
  return r.review_note?.trim() || 'Cố vấn đã duyệt yêu cầu. Vui lòng cập nhật và gửi lại.';
};

const reviewerName = (r?: ReopenRequest | null) => r?.reviewer?.name ?? 'Cố vấn';

/* ==================== Format helpers ==================== */
const toDateInput = (v?: string | null) => (v ? String(v).slice(0, 10) : '');
const toTimeInput = (v?: string | null) => (v ? String(v).slice(0, 5) : '');

const formatDate = (date?: string | null) => {
  if (!date) return '—';
  const d = new Date(date);
  return Number.isNaN(d.getTime())
    ? '—'
    : new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d);
};

const formatDateTime = (v?: string | null) => {
  if (!v) return '';
  const d = new Date(v);
  return Number.isNaN(d.getTime())
    ? ''
    : new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      }).format(d);
};

const formatVND = (value?: number | null) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(value) || 0);

const carLabel = (id?: number | '' | null) => {
  const c = cars.value.find((x) => x.id === Number(id));
  return c ? `${c.id} - ${c.license_plate}` : '—';
};

/* ==================== Dialog state ==================== */
const mode        = ref<DialogMode>('create');
const dialogOpen  = ref(false);
const deleteOpen  = ref(false);
const activeTrip  = ref<Trip | null>(null);   // trip đang mở trong dialog chính
const selected    = ref<Trip | null>(null);   // trip cho dialog xoá

const isCreate = computed(() => mode.value === 'create');
const isEdit   = computed(() => mode.value === 'edit');
const isView   = computed(() => mode.value === 'view');

const dialogTitle = computed(() => {
  if (isCreate.value) return 'Tạo chuyến đi mới';
  const prefix = isView.value ? 'Chi tiết chuyến' : 'Chỉnh sửa chuyến';
  return `${prefix} #${model.value.id} · ${formatDate(model.value.day)}`;
});

/* ==================== Sub dialogs ==================== */
const reopenRequestOpen = ref(false);   // driver gửi yêu cầu mở khoá
const reopenReviewOpen  = ref(false);   // advisor duyệt / từ chối yêu cầu
const rejectTripOpen    = ref(false);   // advisor từ chối chuyến
const target       = ref<Trip | null>(null);
const reviewAction = ref<'approve' | 'reject'>('approve');

/** Đóng dialog chính (nếu đang mở) rồi mới mở dialog con → tránh xung đột focus trap */
const closeMainThen = (fn: () => void) => {
  if (dialogOpen.value) {
    dialogOpen.value = false;
    setTimeout(fn, 180);
  } else {
    fn();
  }
};

const openReopenRequest = (t?: Trip | null) => {
  if (!t || !canRequestReopen(t)) return;
  closeMainThen(() => { target.value = t; reopenRequestOpen.value = true; });
};

const openReopenReview = (t: Trip | null | undefined, action: 'approve' | 'reject') => {
  if (!t || !canReviewReopen(t)) return;
  closeMainThen(() => { target.value = t; reviewAction.value = action; reopenReviewOpen.value = true; });
};

const openRejectTrip = (t?: Trip | null) => {
  if (!t || !canReview(t)) return;
  closeMainThen(() => { target.value = t; rejectTripOpen.value = true; });
};

const openDelete = (t?: Trip | null) => {
  if (!t || !canDelete(t)) return;
  closeMainThen(() => { selected.value = t; deleteOpen.value = true; });
};

/* Xác nhận chuyến — dùng router.patch để không lồng <form> trong footer edit */
const confirmingId = ref<number | null>(null);

const confirmTrip = (t?: Trip | null) => {
  if (!t || !canReview(t) || confirmingId.value) return;
  confirmingId.value = t.id;
  router.patch(`/trips/${t.id}/confirm`, {}, {
    preserveScroll: true,
    onSuccess: () => { if (activeTrip.value?.id === t.id) dialogOpen.value = false; },
    onFinish: () => { confirmingId.value = null; },
  });
};

/* ==================== Form model ==================== */
const toIdOrEmpty = (value?: number | string | null) => {
  if (value === null || value === undefined || value === '') return '' as number | '';
  const num = Number(value);
  return Number.isNaN(num) ? ('' as number | '') : num;
};

const emptyForm = () => ({
  id: 0,
  advisor_id: '' as number | '', driver_id: '' as number | '', car_id: '' as number | '',
  day: '', origin: '', destination: '',
  departure_time: '', arrival_time: '',
  odo_start: 0, odo_end: 0, overtime: 0,
  toll_fee: 0, airport_fee: 0,
  is_overnight: false, is_holiday: false,
  note: '' as string,
  status: 'pending' as TripStatus,
  distance: 0,
  total_fee: 0,
  advisor: null as Advisor | null,
  driver: null as Driver | null,
  reject_reason: null as string | null,
  reviewer: null as PersonRef | null,
  pending_reopen_request: null as ReopenRequest | null,
  latest_reopen_request: null as ReopenRequest | null,
});

const model = ref(emptyForm());

const mapTripToModel = (trip: Trip) => ({
  ...emptyForm(),
  ...trip,
  advisor_id: toIdOrEmpty(trip.advisor?.id),
  driver_id:  toIdOrEmpty(trip.driver?.id),
  car_id: Number(trip.car_id),
  day: toDateInput(trip.day),
  departure_time: toTimeInput(trip.departure_time),
  arrival_time:   toTimeInput(trip.arrival_time),
  overtime:     Number(trip.trip_expense?.overtime ?? 0),
  toll_fee:     Number(trip.trip_expense?.toll_fee ?? 0),
  airport_fee:  Number(trip.trip_expense?.airport_fee ?? 0),
  is_overnight: Boolean(trip.trip_expense?.is_overnight),
  is_holiday:   Boolean(trip.trip_expense?.is_holiday),
  note: trip.note ?? '',
  status: trip.status,
  reject_reason: trip.reject_reason ?? null,
  reviewer: trip.reviewer ?? null,
  pending_reopen_request: trip.pending_reopen_request ?? null,
  latest_reopen_request:  trip.latest_reopen_request ?? null,
});

/* ==================== Open dialog ==================== */
const openCreate = () => {
  mode.value = 'create';
  activeTrip.value = null;
  model.value = emptyForm();
  if (isDriver.value && authUser.value?.id)  model.value.driver_id  = authUser.value.id;
  if (isAdvisor.value && authUser.value?.id) model.value.advisor_id = authUser.value.id;
  resetUploads();
  existingImages.value = [];
  dialogOpen.value = true;
};

const openView = (trip: Trip) => {
  mode.value = 'view';
  activeTrip.value = trip;
  model.value = mapTripToModel(trip);
  resetUploads();
  existingImages.value = [...(trip.images ?? [])];
  dialogOpen.value = true;
};

const openEdit = (trip?: Trip | null) => {
  if (!trip) return;
  if (!canUpdate(trip)) {
    alert(updateHint(trip));
    return;
  }
  mode.value = 'edit';
  activeTrip.value = trip;
  model.value = mapTripToModel(trip);
  resetUploads();
  existingImages.value = [...(trip.images ?? [])];
  dialogOpen.value = true;
};

/** View → Edit ngay trong dialog, không cần đóng */
const switchToEdit = () => openEdit(activeTrip.value);

/** Edit → View (huỷ chỉnh sửa, quay lại xem) */
const switchToView = async () => {
  await discardOrphans();
  if (activeTrip.value) openView(activeTrip.value);
  else dialogOpen.value = false;
};

/* ==================== Dialog computed ==================== */
const distance = computed(() =>
  Math.max(0, (Number(model.value.odo_end) || 0) - (Number(model.value.odo_start) || 0)),
);

const formProps = computed(() =>
  isCreate.value ? trips.store.form() : trips.update.form(model.value.id),
);

const dialogReopen         = computed<ReopenRequest | null>(() => model.value.latest_reopen_request ?? null);
const dialogPendingReopen  = computed<ReopenRequest | null>(() => model.value.pending_reopen_request ?? null);

const dialogApprovedReopen = computed<ReopenRequest | null>(() =>
  model.value.status === 'editing' && dialogReopen.value?.status === 'approved' ? dialogReopen.value : null,
);

const dialogRejectedReopen = computed<ReopenRequest | null>(() =>
  model.value.status === 'confirmed' && dialogReopen.value?.status === 'rejected' ? dialogReopen.value : null,
);

const submitWarning = computed<string | null>(() => {
  if (!isEdit.value || !isDriver.value) return null;
  switch (model.value.status) {
    case 'pending':
      return 'Chuyến đang chờ cố vấn duyệt. Nếu cố vấn xử lý trước khi bạn lưu, thay đổi có thể không được ghi nhận.';
    case 'editing':
      return 'Sau khi lưu, chuyến sẽ chuyển sang "Chờ duyệt" và bạn không thể sửa tiếp cho tới khi cố vấn phản hồi.';
    case 'rejected':
      return 'Sau khi lưu, chuyến sẽ được gửi lại cho cố vấn duyệt.';
    default:
      return null;
  }
});

/** Có nút hành động nào khả dụng trong dialog không? */
const hasDialogActions = computed(() => {
  const t = activeTrip.value;
  return !!t && (canUpdate(t) || canDelete(t) || canRequestReopen(t) || canReview(t) || canReviewReopen(t));
});

/* ==================== Cloudinary upload ==================== */
const MAX_IMAGES = 10;

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
const previewImage   = ref<string | null>(null);

const totalImages    = computed(() => existingImages.value.length + uploadItems.value.length);
const remainingSlots = computed(() => Math.max(0, MAX_IMAGES - totalImages.value));

const onPickFiles = async (e: Event) => {
  const input = e.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  input.value = '';
  if (files.length) await addFiles(files, remainingSlots.value);
};

const removeExisting = (img: TripImage) => {
  existingImages.value = existingImages.value.filter((i) => i.id !== img.id);
};

const closeDialog = async (discard = false) => {
  if (discard) await discardOrphans();
  else resetUploads();
  existingImages.value = [];
  activeTrip.value = null;
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
        <Button v-if="props.can?.create" size="sm" @click="openCreate">
          Tạo chuyến đi
        </Button>
      </div>
    </CardHeader>

    <CardContent>
      <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
        <table class="w-full table-auto border-collapse">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-800">
              <th v-for="h in ['ID','Advisor','Driver','Origin','Destination','Day','Distance',
                               'Status','Overnight','Holiday','Total fee','Actions']"
                  :key="h"
                  class="border border-gray-300 px-2 py-2 text-center dark:border-gray-700">
                {{ h }}
              </th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="!items.length">
              <td colspan="12" class="px-4 py-8 text-center text-muted-foreground">Chưa có dữ liệu.</td>
            </tr>

            <tr v-for="trip in items" :key="trip.id" class="hover:bg-gray-50 dark:hover:bg-gray-900">
              <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.id }}</td>
              <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.advisor?.name ?? '—' }}</td>
              <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.driver?.name ?? '—' }}</td>
              <td class="max-w-[150px] truncate border px-2 py-2 text-center align-top dark:border-gray-700"
                  :title="trip.origin">{{ trip.origin }}</td>
              <td class="max-w-[150px] truncate border px-2 py-2 text-center align-top dark:border-gray-700"
                  :title="trip.destination">{{ trip.destination }}</td>
              <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ formatDate(trip.day) }}</td>
              <td class="border px-2 py-2 text-center align-top dark:border-gray-700">{{ trip.distance }}</td>

              <!-- Status + ghi chú ngữ cảnh -->
              <td class="border px-2 py-2 text-center align-top dark:border-gray-700">
                <Badge class="inline-flex min-w-[88px] justify-center text-white"
                       :class="STATUS_CLASS[trip.status] ?? 'bg-gray-500'">
                  {{ STATUS_LABEL[trip.status] ?? trip.status }}
                </Badge>

                <p v-if="trip.status === 'rejected' && trip.reject_reason"
                   class="mx-auto mt-1 max-w-[240px] text-left text-xs text-red-600 dark:text-red-400"
                   :title="trip.reject_reason">
                  <span class="font-medium">{{ trip.reviewer?.name ?? 'Cố vấn' }} từ chối:</span>
                  {{ trip.reject_reason }}
                </p>

                <p v-if="trip.pending_reopen_request"
                   class="mx-auto mt-1 max-w-[240px] text-left text-xs text-indigo-600 dark:text-indigo-400"
                   :title="trip.pending_reopen_request.reason">
                  <span class="font-medium">Chờ duyệt mở khoá:</span>
                  {{ trip.pending_reopen_request.reason }}
                </p>

                <p v-if="editingNote(trip)"
                   class="mx-auto mt-1 max-w-[240px] text-left text-xs text-indigo-600 dark:text-indigo-400"
                   :title="editingNote(trip) ?? ''">
                  <span class="font-medium">{{ reviewerName(approvedReopen(trip)) }} duyệt:</span>
                  {{ editingNote(trip) }}
                </p>

                <p v-if="trip.status === 'confirmed' && rejectedReopen(trip)"
                   class="mx-auto mt-1 max-w-[240px] text-left text-xs text-orange-600 dark:text-orange-400"
                   :title="rejectedReopen(trip)?.review_note ?? ''">
                  <span class="font-medium">{{ reviewerName(rejectedReopen(trip)) }} từ chối mở khoá:</span>
                  {{ rejectedReopen(trip)?.review_note }}
                </p>
              </td>

              <td class="border px-2 py-2 align-top dark:border-gray-700">
                <div class="flex items-center justify-center">
                  <CircleCheckBigIcon v-if="trip.trip_expense?.is_overnight" class="text-green-500" />
                  <CircleX v-else class="text-gray-400" />
                </div>
              </td>
              <td class="border px-2 py-2 align-top dark:border-gray-700">
                <div class="flex items-center justify-center">
                  <CircleCheckBigIcon v-if="trip.trip_expense?.is_holiday" class="text-green-500" />
                  <CircleX v-else class="text-gray-400" />
                </div>
              </td>

              <td class="border px-2 py-2 text-right align-top dark:border-gray-700">
                {{ formatVND(trip.total_fee) }}
              </td>

              <!-- Actions -->
              <td class="border px-2 py-2 align-top dark:border-gray-700">
                <div class="flex flex-wrap justify-center gap-1.5">
                  <Button variant="ghost" size="sm" title="Xem chi tiết chuyến đi"
                          @click="openView(trip)">
                    <Eye class="h-4 w-4" />
                  </Button>

                  <Button v-if="canUpdate(trip)" variant="outline" size="sm"
                          :title="updateHint(trip)" @click="openEdit(trip)">
                    <Pencil class="mr-1 h-3.5 w-3.5" />
                    {{ editButtonLabel(trip) }}
                  </Button>

                  <Button v-if="canRequestReopen(trip)" variant="secondary" size="sm"
                          title="Gửi yêu cầu mở khoá cho cố vấn duyệt"
                          @click="openReopenRequest(trip)">
                    <Unlock class="mr-1 h-3.5 w-3.5" /> Xin mở khoá
                  </Button>

                  <Button v-if="canDelete(trip)" variant="destructive" size="sm"
                          :title="deleteHint(trip)" @click="openDelete(trip)">
                    <Trash2 class="mr-1 h-3.5 w-3.5" /> Xoá
                  </Button>

                  <!-- Advisor / Admin: duyệt yêu cầu mở khoá -->
                  <template v-if="canReviewReopen(trip)">
                    <Button variant="default" size="sm" title="Đồng ý cho tài xế cập nhật lại chuyến"
                            @click="openReopenReview(trip, 'approve')">
                      <ThumbsUp class="mr-1 h-3.5 w-3.5" /> Duyệt mở khoá
                    </Button>
                    <Button variant="outline" size="sm" title="Từ chối cho tài xế cập nhật lại chuyến"
                            @click="openReopenReview(trip, 'reject')">
                      <ThumbsDown class="mr-1 h-3.5 w-3.5" /> Từ chối mở khoá
                    </Button>
                  </template>

                  <!-- Advisor / Admin: confirm / reject chuyến -->
                  <template v-if="canReview(trip)">
                    <Button size="sm" variant="default" title="Xác nhận thông tin chuyến đi chính xác"
                            :disabled="confirmingId === trip.id" @click="confirmTrip(trip)">
                      <Loader2 v-if="confirmingId === trip.id" class="mr-1 h-3.5 w-3.5 animate-spin" />
                      <Check v-else class="mr-1 h-3.5 w-3.5" />
                      Xác nhận
                    </Button>
                    <Button variant="destructive" size="sm" title="Thông tin chuyến đi không chính xác"
                            @click="openRejectTrip(trip)">
                      <Ban class="mr-1 h-3.5 w-3.5" /> Từ chối
                    </Button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="links.length" class="mt-4 flex justify-center gap-2">
        <Link v-for="(link, i) in links" :key="i"
              :href="link.url ?? trips.index().url"
              class="rounded-md border border-gray-300 px-3 py-1 dark:border-gray-700"
              :class="[
                link.active ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted/60',
                !link.url ? 'pointer-events-none opacity-50' : '',
              ]">
          <span v-html="link.label" />
        </Link>
      </div>
    </CardContent>
  </Card>

  <!-- ==================== Create / Edit / View ==================== -->
  <Dialog v-model:open="dialogOpen">
    <DialogContent class="flex max-h-[92vh] flex-col sm:max-w-[760px]">
      <DialogHeader>
        <DialogTitle class="flex flex-wrap items-center gap-2">
          <span>{{ dialogTitle }}</span>
          <Badge v-if="!isCreate" class="text-white" :class="STATUS_CLASS[model.status]">
            {{ STATUS_LABEL[model.status] }}
          </Badge>
        </DialogTitle>
        <DialogDescription v-if="isView">
          Chế độ xem — dữ liệu chỉ đọc. Dùng các nút bên dưới để thao tác.
        </DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto px-1">

        <!-- ===== Banner ngữ cảnh (dùng chung cho edit & view) ===== -->
        <div v-if="!isCreate" class="mb-4 space-y-3">
          <!-- Cố vấn từ chối chuyến -->
          <div v-if="model.status === 'rejected' && model.reject_reason"
               class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm
                      text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200">
            <p class="font-semibold">
              Cố vấn {{ model.reviewer?.name ?? '' }} đã từ chối chuyến này
            </p>
            <p class="mt-1">Lý do: {{ model.reject_reason }}</p>
            <p v-if="isEdit" class="mt-2 text-xs opacity-80">
              Vui lòng chỉnh sửa và lưu lại — chuyến sẽ được gửi duyệt lần nữa.
            </p>
          </div>

          <!-- Yêu cầu mở khoá đã được duyệt (status = editing) -->
          <div v-if="dialogApprovedReopen"
               class="rounded-md border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm
                      text-indigo-900 dark:border-indigo-700 dark:bg-indigo-950 dark:text-indigo-100">
            <p class="font-semibold">
              {{ dialogApprovedReopen.reviewer?.name ?? 'Cố vấn' }} đã duyệt yêu cầu mở khoá
              <span v-if="dialogApprovedReopen.reviewed_at" class="font-normal opacity-75">
                · {{ formatDateTime(dialogApprovedReopen.reviewed_at) }}
              </span>
            </p>
            <p v-if="dialogApprovedReopen.review_note" class="mt-1">
              <span class="font-medium">Ghi chú:</span> {{ dialogApprovedReopen.review_note }}
            </p>
            <p v-else class="mt-1 italic opacity-80">Cố vấn không để lại ghi chú.</p>
            <p class="mt-2 border-t border-indigo-200 pt-2 text-xs dark:border-indigo-800">
              <span class="font-medium">Lý do đã gửi:</span> "{{ dialogApprovedReopen.reason }}"
            </p>
          </div>

          <!-- Yêu cầu mở khoá bị từ chối -->
          <div v-if="dialogRejectedReopen"
               class="rounded-md border border-orange-300 bg-orange-50 px-3 py-2 text-sm
                      text-orange-900 dark:border-orange-700 dark:bg-orange-950 dark:text-orange-100">
            <p class="font-semibold">
              {{ dialogRejectedReopen.reviewer?.name ?? 'Cố vấn' }} đã từ chối yêu cầu mở khoá
              <span v-if="dialogRejectedReopen.reviewed_at" class="font-normal opacity-75">
                · {{ formatDateTime(dialogRejectedReopen.reviewed_at) }}
              </span>
            </p>
            <p class="mt-1">
              <span class="font-medium">Ghi chú:</span> {{ dialogRejectedReopen.review_note }}
            </p>
          </div>

          <!-- Đang chờ duyệt yêu cầu mở khoá -->
          <div v-if="dialogPendingReopen"
               class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm
                      text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <p class="font-semibold">Đang chờ duyệt yêu cầu mở khoá</p>
            <p class="mt-1">
              <span class="font-medium">
                {{ dialogPendingReopen.requester?.name ?? 'Tài xế' }}:
              </span>
              "{{ dialogPendingReopen.reason }}"
            </p>
            <p class="mt-1 text-xs opacity-75">
              Gửi lúc {{ formatDateTime(dialogPendingReopen.created_at) }}
            </p>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- ===================== CHẾ ĐỘ XEM =========================== -->
        <!-- ============================================================ -->
        <div v-if="isView" class="space-y-4">
          <!-- Thông tin chung -->
          <div class="rounded-md border p-3">
            <p class="mb-3 text-sm font-semibold">Thông tin chuyến</p>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-3">
              <div>
                <dt class="text-xs text-muted-foreground">Cố vấn</dt>
                <dd class="font-medium">{{ model.advisor?.name ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Tài xế</dt>
                <dd class="font-medium">{{ model.driver?.name ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Xe</dt>
                <dd class="font-medium">{{ carLabel(model.car_id) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Ngày</dt>
                <dd class="font-medium">{{ formatDate(model.day) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Giờ đi</dt>
                <dd class="font-medium">{{ model.departure_time || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Giờ đến</dt>
                <dd class="font-medium">{{ model.arrival_time || '—' }}</dd>
              </div>
              <div class="col-span-2 sm:col-span-3">
                <dt class="text-xs text-muted-foreground">Hành trình</dt>
                <dd class="font-medium">{{ model.origin }} → {{ model.destination }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Odo bắt đầu</dt>
                <dd class="font-medium">{{ model.odo_start }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Odo kết thúc</dt>
                <dd class="font-medium">{{ model.odo_end }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Quãng đường</dt>
                <dd class="font-medium">{{ model.distance }} km</dd>
              </div>
              <div class="col-span-2 sm:col-span-3">
                <dt class="text-xs text-muted-foreground">Ghi chú</dt>
                <dd class="font-medium">{{ model.note || '—' }}</dd>
              </div>
            </dl>
          </div>

          <!-- Chi phí -->
          <div class="rounded-md border p-3">
            <p class="mb-3 text-sm font-semibold">Chi phí</p>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-3">
              <div>
                <dt class="text-xs text-muted-foreground">Tăng ca</dt>
                <dd class="font-medium">{{ model.overtime }} giờ</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Phí cầu đường</dt>
                <dd class="font-medium">{{ formatVND(model.toll_fee) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Phí sân bay / gửi xe</dt>
                <dd class="font-medium">{{ formatVND(model.airport_fee) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Nghỉ đêm</dt>
                <dd class="flex items-center gap-1 font-medium">
                  <CircleCheckBigIcon v-if="model.is_overnight" class="h-4 w-4 text-green-500" />
                  <CircleX v-else class="h-4 w-4 text-gray-400" />
                  {{ model.is_overnight ? 'Có' : 'Không' }}
                </dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Ngày lễ</dt>
                <dd class="flex items-center gap-1 font-medium">
                  <CircleCheckBigIcon v-if="model.is_holiday" class="h-4 w-4 text-green-500" />
                  <CircleX v-else class="h-4 w-4 text-gray-400" />
                  {{ model.is_holiday ? 'Có' : 'Không' }}
                </dd>
              </div>
              <div>
                <dt class="text-xs text-muted-foreground">Tổng chi phí</dt>
                <dd class="text-base font-semibold text-emerald-700 dark:text-emerald-400">
                  {{ formatVND(model.total_fee) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Ảnh -->
          <div class="rounded-md border p-3">
            <div class="mb-3 flex items-center justify-between">
              <p class="text-sm font-semibold">Hình ảnh chuyến đi</p>
              <span class="text-xs text-muted-foreground">{{ existingImages.length }} ảnh</span>
            </div>

            <div v-if="existingImages.length" class="grid grid-cols-4 gap-3 sm:grid-cols-5">
              <div v-for="img in existingImages" :key="'view-' + img.id"
                   class="aspect-square overflow-hidden rounded-md border">
                <img :src="thumb(img.url)" :alt="'Ảnh ' + img.id"
                     class="h-full w-full cursor-zoom-in object-cover transition hover:scale-105"
                     @click="previewImage = img.url" />
              </div>
            </div>
            <p v-else class="py-4 text-center text-sm text-muted-foreground">
              Chuyến này chưa có ảnh đính kèm.
            </p>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- ================= CHẾ ĐỘ TẠO / CHỈNH SỬA =================== -->
        <!-- ============================================================ -->
        <Form
          v-else
          :key="mode + '-' + model.id"
          v-bind="formProps"
          v-slot="{ errors, processing }"
          class="space-y-4"
          @success="closeDialog(false)"
        >
          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="advisor_id">Cố vấn</Label>
              <select id="advisor_id" v-model="model.advisor_id" name="advisor_id" required
                      class="w-full rounded border p-2">
                <option value="" disabled>-- Chọn cố vấn --</option>
                <option v-for="a in advisors" :key="a.id" :value="a.id">{{ a.id }} - {{ a.name }}</option>
              </select>
              <InputError :message="errors?.advisor_id" />
            </div>

            <div class="grid gap-2">
              <Label for="driver_id">Tài xế</Label>
              <div v-if="isDriver" class="flex items-center rounded border bg-muted p-2 text-sm">
                {{ authUser?.id }} - {{ authUser?.name }}
                <input type="hidden" name="driver_id" :value="authUser?.id" />
              </div>
              <select v-else id="driver_id" v-model="model.driver_id" name="driver_id" required
                      class="w-full rounded border p-2">
                <option value="" disabled>-- Chọn tài xế --</option>
                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.id }} - {{ d.name }}</option>
              </select>
              <InputError :message="errors?.driver_id" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="f-day">Ngày</Label>
              <Input id="f-day" v-model="model.day" type="date" name="day" required />
              <InputError :message="errors?.day" />
            </div>
            <div class="grid gap-2">
              <Label for="f-car">Xe</Label>
              <select id="f-car" v-model="model.car_id" name="car_id" required class="w-full rounded border p-2">
                <option value="" disabled>-- Chọn xe --</option>
                <option v-for="c in cars" :key="c.id" :value="c.id">{{ c.id }} - {{ c.license_plate }}</option>
              </select>
              <InputError :message="errors?.car_id" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="f-origin">Điểm đi</Label>
              <Input id="f-origin" v-model="model.origin" name="origin" required />
              <InputError :message="errors?.origin" />
            </div>
            <div class="grid gap-2">
              <Label for="f-destination">Điểm đến</Label>
              <Input id="f-destination" v-model="model.destination" name="destination" required />
              <InputError :message="errors?.destination" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="f-dep">Giờ đi</Label>
              <Input id="f-dep" v-model="model.departure_time" type="time" name="departure_time" required />
              <InputError :message="errors?.departure_time" />
            </div>
            <div class="grid gap-2">
              <Label for="f-arr">Giờ đến</Label>
              <Input id="f-arr" v-model="model.arrival_time" type="time" name="arrival_time" required />
              <InputError :message="errors?.arrival_time" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
              <Label for="f-odos">Odo bắt đầu</Label>
              <Input id="f-odos" v-model.number="model.odo_start" type="number" min="0" name="odo_start" required />
              <InputError :message="errors?.odo_start" />
            </div>
            <div class="grid gap-2">
              <Label for="f-odoe">Odo kết thúc</Label>
              <Input id="f-odoe" v-model.number="model.odo_end" type="number"
                     :min="model.odo_start || 0" name="odo_end" required />
              <InputError :message="errors?.odo_end" />
            </div>
            <div class="grid gap-2">
              <Label for="f-dist">Quãng đường (tự tính)</Label>
              <Input id="f-dist" :model-value="distance" type="number" name="distance" readonly class="bg-muted" />
              <InputError :message="errors?.distance" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
              <Label for="f-ot">Tăng ca (giờ)</Label>
              <Input id="f-ot" v-model.number="model.overtime" type="number" min="0" max="4" name="overtime" />
              <InputError :message="errors?.overtime" />
            </div>
            <div class="grid gap-2">
              <Label for="f-toll">Phí cầu đường</Label>
              <Input id="f-toll" v-model.number="model.toll_fee" type="number" min="0" name="toll_fee" />
              <InputError :message="errors?.toll_fee" />
            </div>
            <div class="grid gap-2">
              <Label for="f-air">Phí sân bay / gửi xe</Label>
              <Input id="f-air" v-model.number="model.airport_fee" type="number" min="0" name="airport_fee" />
              <InputError :message="errors?.airport_fee" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2 rounded-md border p-3">
              <div class="flex items-center gap-2">
                <Checkbox id="f-overnight-check"
                          :model-value="model.is_overnight"
                          @update:model-value="(v: any) => (model.is_overnight = v === true)" />
                <Label for="f-overnight-check" class="cursor-pointer">Nghỉ đêm</Label>
              </div>
              <input type="hidden" name="is_overnight" :value="model.is_overnight ? 1 : 0" />
              <InputError :message="errors?.is_overnight" />
            </div>

            <div class="grid gap-2 rounded-md border p-3">
              <div class="flex items-center gap-2">
                <Checkbox id="f-holiday-check"
                          :model-value="model.is_holiday"
                          @update:model-value="(v: any) => (model.is_holiday = v === true)" />
                <Label for="f-holiday-check" class="cursor-pointer">Ngày lễ</Label>
              </div>
              <input type="hidden" name="is_holiday" :value="model.is_holiday ? 1 : 0" />
              <InputError :message="errors?.is_holiday" />
            </div>
          </div>

          <!-- Hình ảnh -->
          <div class="grid gap-3 rounded-md border p-3">
            <div class="flex items-center justify-between">
              <Label class="font-medium">Hình ảnh chuyến đi</Label>
              <span class="text-xs text-muted-foreground">{{ totalImages }}/{{ MAX_IMAGES }} ảnh</span>
            </div>

            <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border
                          border-dashed px-4 py-6 text-sm text-muted-foreground transition hover:bg-muted/50"
                   :class="remainingSlots === 0 ? 'pointer-events-none opacity-50' : ''">
              <ImagePlus class="h-5 w-5" />
              <span>Nhấn để chọn ảnh (JPG, PNG, WEBP · tối đa 5MB/ảnh)</span>
              <input type="file" class="hidden" multiple
                     accept="image/jpeg,image/png,image/webp,image/heic"
                     :disabled="remainingSlots === 0" @change="onPickFiles" />
            </label>

            <div v-if="totalImages" class="grid grid-cols-4 gap-3 sm:grid-cols-5">
              <div v-for="img in existingImages" :key="'old-' + img.id"
                   class="group relative aspect-square overflow-hidden rounded-md border">
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

              <div v-for="item in uploadItems" :key="item.uid"
                   class="group relative aspect-square overflow-hidden rounded-md border"
                   :class="item.status === 'error' ? 'border-red-500' : ''">
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

              <template v-for="(img, i) in uploadedImages" :key="'up-' + img.public_id">
                <input type="hidden" :name="`images[${i}][public_id]`" :value="img.public_id" />
                <input type="hidden" :name="`images[${i}][url]`"       :value="img.url" />
                <input type="hidden" :name="`images[${i}][format]`"    :value="img.format ?? ''" />
                <input type="hidden" :name="`images[${i}][width]`"     :value="img.width ?? ''" />
                <input type="hidden" :name="`images[${i}][height]`"    :value="img.height ?? ''" />
                <input type="hidden" :name="`images[${i}][bytes]`"     :value="img.bytes ?? ''" />
              </template>
            </div>

            <!-- Luôn gửi, kể cả khi đã xoá hết ảnh -->
            <input type="hidden" name="images_synced" value="1" />
            <input v-for="img in existingImages" :key="'keep-' + img.id"
                   type="hidden" name="kept_image_ids[]" :value="img.id" />
            <InputError :message="errors?.images" />
          </div>

          <div class="grid gap-2">
            <Label for="f-note">Ghi chú</Label>
            <Input id="f-note" v-model="model.note" name="note" />
            <InputError :message="errors?.note" />
          </div>

          <div v-if="submitWarning"
               class="rounded-md border px-3 py-2 text-sm"
               :class="model.status === 'pending'
                 ? 'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-700 dark:bg-sky-950 dark:text-sky-200'
                 : 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200'">
            {{ submitWarning }}
          </div>

          <!-- Footer chế độ tạo / sửa -->
          <DialogFooter class="gap-2 sm:justify-between">
            <!-- Hành động phụ (chỉ ở chế độ sửa) -->
            <div class="flex flex-wrap gap-2">
              <Button v-if="isEdit && canDelete(activeTrip)" type="button" variant="destructive" size="sm"
                      :disabled="processing" :title="deleteHint(activeTrip!)"
                      @click="openDelete(activeTrip)">
                <Trash2 class="mr-1 h-3.5 w-3.5" /> Xoá chuyến
              </Button>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
              <Button type="button" variant="outline" :disabled="processing"
                      @click="isEdit && activeTrip ? switchToView() : closeDialog(true)">
                {{ isEdit && activeTrip ? 'Quay lại xem' : 'Huỷ' }}
              </Button>
              <Button type="submit" :disabled="processing || isUploading">
                <Loader2 v-if="processing || isUploading" class="mr-2 h-4 w-4 animate-spin" />
                {{ isUploading ? 'Đang tải ảnh...' : (isCreate ? 'Tạo chuyến' : 'Lưu & gửi duyệt') }}
              </Button>
            </div>
          </DialogFooter>
        </Form>
      </div>

      <!-- Footer chế độ xem: đầy đủ nút theo policy -->
      <DialogFooter v-if="isView" class="gap-2 border-t pt-4 sm:justify-between">
        <div class="flex flex-wrap gap-2">
          <Button v-if="canUpdate(activeTrip)" variant="outline" size="sm"
                  :title="updateHint(activeTrip!)" @click="switchToEdit">
            <Pencil class="mr-1 h-3.5 w-3.5" /> {{ editButtonLabel(activeTrip) }}
          </Button>

          <Button v-if="canRequestReopen(activeTrip)" variant="secondary" size="sm"
                  title="Gửi yêu cầu mở khoá cho cố vấn duyệt"
                  @click="openReopenRequest(activeTrip)">
            <Unlock class="mr-1 h-3.5 w-3.5" /> Xin mở khoá
          </Button>

          <Button v-if="canDelete(activeTrip)" variant="destructive" size="sm"
                  :title="deleteHint(activeTrip!)" @click="openDelete(activeTrip)">
            <Trash2 class="mr-1 h-3.5 w-3.5" /> Xoá
          </Button>

          <template v-if="canReviewReopen(activeTrip)">
            <Button variant="default" size="sm" title="Đồng ý cho tài xế cập nhật lại chuyến"
                    @click="openReopenReview(activeTrip, 'approve')">
              <ThumbsUp class="mr-1 h-3.5 w-3.5" /> Duyệt mở khoá
            </Button>
            <Button variant="outline" size="sm" title="Từ chối cho tài xế cập nhật lại chuyến"
                    @click="openReopenReview(activeTrip, 'reject')">
              <ThumbsDown class="mr-1 h-3.5 w-3.5" /> Từ chối mở khoá
            </Button>
          </template>

          <template v-if="canReview(activeTrip)">
            <Button size="sm" variant="default" title="Xác nhận thông tin chuyến đi chính xác"
                    :disabled="confirmingId === activeTrip?.id" @click="confirmTrip(activeTrip)">
              <Loader2 v-if="confirmingId === activeTrip?.id" class="mr-1 h-3.5 w-3.5 animate-spin" />
              <Check v-else class="mr-1 h-3.5 w-3.5" />
              Xác nhận chuyến
            </Button>
            <Button variant="destructive" size="sm" title="Thông tin chuyến đi không chính xác"
                    @click="openRejectTrip(activeTrip)">
              <Ban class="mr-1 h-3.5 w-3.5" /> Từ chối chuyến
            </Button>
          </template>

          <p v-if="!hasDialogActions" class="self-center text-xs text-muted-foreground">
            Bạn không có thao tác khả dụng với chuyến này.
          </p>
        </div>

        <Button variant="outline" size="sm" @click="closeDialog(false)">Đóng</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- ==================== Delete ==================== -->
  <Dialog v-model:open="deleteOpen">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Xoá chuyến đi</DialogTitle>
        <DialogDescription>Hành động này không thể hoàn tác.</DialogDescription>
      </DialogHeader>

      <Form v-if="selected" v-bind="trips.destroy.form(selected.id)" v-slot="{ processing }"
            class="space-y-4" @success="deleteOpen = false">
        <p class="mb-4 text-sm text-muted-foreground">
          Xoá vĩnh viễn chuyến <b>#{{ selected.id }}</b> — ngày {{ formatDate(selected.day) }},
          {{ selected.origin }} → {{ selected.destination }}.
        </p>
        <DialogFooter>
          <Button type="button" variant="outline" :disabled="processing" @click="deleteOpen = false">Huỷ</Button>
          <Button type="submit" variant="destructive" :disabled="processing">Xoá</Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>

  <!-- ==================== Lightbox ==================== -->
  <Dialog :open="!!previewImage" @update:open="(v) => !v && (previewImage = null)">
    <DialogContent class="sm:max-w-[860px]">
      <DialogHeader>
        <DialogTitle>Xem ảnh</DialogTitle>
      </DialogHeader>
      <img v-if="previewImage" :src="previewImage" alt="Ảnh chuyến đi"
           class="max-h-[70vh] w-full rounded-md object-contain" />
    </DialogContent>
  </Dialog>

  <!-- ==================== Driver gửi yêu cầu mở khoá ==================== -->
  <Dialog v-model:open="reopenRequestOpen">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Yêu cầu mở khoá chỉnh sửa</DialogTitle>
        <DialogDescription>
          Yêu cầu sẽ được gửi tới cố vấn phụ trách. Sau khi được duyệt, chuyến chuyển sang
          trạng thái <b>Đang sửa</b> và bạn có thể cập nhật lại.
        </DialogDescription>
      </DialogHeader>

      <Form v-if="target" :action="`/trips/${target.id}/reopen-requests`" method="post"
            v-slot="{ errors, processing }" class="space-y-4"
            @success="reopenRequestOpen = false">
        <p class="text-sm text-muted-foreground">
          Chuyến <b>#{{ target.id }}</b> — {{ formatDate(target.day) }},
          {{ target.origin }} → {{ target.destination }}
        </p>

        <div class="grid gap-2">
          <Label for="reason">Lý do cần chỉnh sửa <span class="text-red-500">*</span></Label>
          <Textarea id="reason" name="reason" rows="3" required minlength="10"
                    placeholder="VD: Nhập sai odo kết thúc (đúng là 45.320 km), thiếu ảnh hoá đơn phí cầu đường." />
          <InputError :message="errors?.reason" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" :disabled="processing"
                  @click="reopenRequestOpen = false">Huỷ</Button>
          <Button type="submit" :disabled="processing">Gửi yêu cầu</Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>

  <!-- ==================== Advisor duyệt / từ chối yêu cầu ==================== -->
  <Dialog v-model:open="reopenReviewOpen">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>
          {{ reviewAction === 'approve' ? 'Duyệt yêu cầu mở khoá' : 'Từ chối yêu cầu mở khoá' }}
        </DialogTitle>
        <DialogDescription>
          {{ reviewAction === 'approve'
              ? 'Chuyến sẽ chuyển sang "Đang sửa" để tài xế cập nhật.'
              : 'Chuyến giữ nguyên trạng thái "Đã xác nhận".' }}
        </DialogDescription>
      </DialogHeader>

      <Form v-if="target?.pending_reopen_request"
            :action="`/reopen-requests/${target.pending_reopen_request.id}/${reviewAction}`"
            method="patch" v-slot="{ errors, processing }" class="space-y-4"
            @success="reopenReviewOpen = false">
        <div class="rounded-md border bg-muted/40 p-3 text-sm">
          <p class="font-medium">
            Chuyến #{{ target.id }} · Ngày {{ formatDate(target.day) }}<br />
            {{ target.origin }} → {{ target.destination }}
          </p>
          <p class="mt-2 text-muted-foreground">
            <b>Tài xế {{ target.pending_reopen_request.requester?.name ?? '' }}</b> yêu cầu mở lại:
          </p>
          <p class="mt-1 italic">"{{ target.pending_reopen_request.reason }}"</p>
        </div>

        <div class="grid gap-2">
          <Label for="review_note">
            Ghi chú phản hồi
            <span v-if="reviewAction === 'reject'" class="text-red-500">*</span>
          </Label>
          <Textarea id="review_note" name="review_note" rows="3"
                    :required="reviewAction === 'reject'"
                    :placeholder="reviewAction === 'approve'
                      ? 'Tuỳ chọn — VD: Nhớ đính kèm ảnh hoá đơn.'
                      : 'Xin nêu rõ lý do từ chối.'" />
          <InputError :message="errors?.review_note" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" :disabled="processing"
                  @click="reopenReviewOpen = false">Huỷ</Button>
          <Button type="submit" :disabled="processing"
                  :variant="reviewAction === 'approve' ? 'default' : 'destructive'">
            {{ reviewAction === 'approve' ? 'Duyệt mở khoá' : 'Từ chối yêu cầu' }}
          </Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>

  <!-- ==================== Advisor từ chối chuyến ==================== -->
  <Dialog v-model:open="rejectTripOpen">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Từ chối chuyến</DialogTitle>
        <DialogDescription>
          Chuyến chuyển sang <b>Bị từ chối</b>. Tài xế có thể sửa lại và gửi duyệt lần nữa.
        </DialogDescription>
      </DialogHeader>

      <Form v-if="target" :action="`/trips/${target.id}/reject`" method="patch"
            v-slot="{ errors, processing }" class="space-y-4"
            @success="rejectTripOpen = false">
        <p class="text-sm text-muted-foreground">
          Chuyến <b>#{{ target.id }}</b> — {{ formatDate(target.day) }},
          {{ target.origin }} → {{ target.destination }}
        </p>

        <div class="grid gap-2">
          <Label for="reject_reason">Lý do từ chối <span class="text-red-500">*</span></Label>
          <Textarea id="reject_reason" name="reject_reason" rows="3" required minlength="5"
                    placeholder="VD: Odo kết thúc không khớp ảnh chụp đồng hồ." />
          <InputError :message="errors?.reject_reason" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" :disabled="processing"
                  @click="rejectTripOpen = false">Huỷ</Button>
          <Button type="submit" variant="destructive" :disabled="processing">Từ chối chuyến</Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>