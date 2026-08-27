<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
  } from '@/components/ui/select'
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
  } from '@/components/ui/table'
import { Download, Loader2, FileSpreadsheet } from 'lucide-vue-next'

interface CarOption { id: number; label: string }
interface TripRow {
  id: number
  date: string | null
  itinerary: string
  odo_start: number
  odo_end: number
  distance: number
  time_start: string | null
  time_end: string | null
  overtime: number
  overnight: number
  toll_air: number
  holiday: number
}

const props = defineProps<{
  cars: CarOption[]
  filters: { month: string; car_id: number | null }
  trips: TripRow[]
}>()

const month = ref<string>(props.filters.month ?? new Date().toISOString().slice(0, 7))
const carId = ref<string | undefined>(props.filters.car_id ? String(props.filters.car_id) : undefined)
const downloading = ref(false)

/* Danh sách 24 tháng gần nhất cho Select */
const monthOptions = computed(() => {
  const list: { value: string; label: string }[] = []
  const now = new Date()
  for (let i = 0; i < 24; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    list.push({ value, label: `Tháng ${d.getMonth() + 1}/${d.getFullYear()}` })
  }
  return list
})

const canExport = computed(() => !!month.value && !!carId.value && props.trips.length > 0)

const totals = computed(() => props.trips.reduce((acc, t) => ({
  distance: acc.distance + t.distance,
  overtime: acc.overtime + t.overtime,
  overnight: acc.overnight + t.overnight,
  toll_air: acc.toll_air + t.toll_air,
  holiday: acc.holiday + t.holiday,
}), { distance: 0, overtime: 0, overnight: 0, toll_air: 0, holiday: 0 }))

const nf = new Intl.NumberFormat('vi-VN')
const fmt = (n: number) => (n ? nf.format(n) : '-')

/* Tải lại preview (partial reload) khi đổi bộ lọc */
function reload() {
  router.get('/admin/trips/export',
    { month: month.value, car_id: carId.value ?? '' },
    { only: ['trips', 'filters'], preserveState: true, preserveScroll: true, replace: true },
  )
}

watch([month, carId], reload)

/* Tải file: dùng điều hướng trình duyệt, không dùng Inertia visit */
function downloadExcel() {
  if (!canExport.value) return
  downloading.value = true
  const query = new URLSearchParams({ month: month.value, car_id: String(carId.value) })
  window.location.href = `/admin/trips/export/download?${query.toString()}`
  setTimeout(() => (downloading.value = false), 1500)
}
</script>

<template>
  <Head title="Xuất Excel chuyến đi" />
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <FileSpreadsheet class="size-5" />
            Xuất Excel — Trips
          </CardTitle>
          <CardDescription>
            Chỉ xuất các chuyến đi có trạng thái
            <Badge variant="secondary">confirmed</Badge>
            theo tháng và xe được chọn.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <div class="grid gap-2">
              <Label for="month">Tháng</Label>
              <Select id="month" v-model="month">
                <SelectTrigger><SelectValue placeholder="Chọn tháng" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="m in monthOptions" :key="m.value" :value="m.value">
                    {{ m.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="grid gap-2">
              <Label for="car">Xe</Label>
              <Select id="car" v-model="carId">
                <SelectTrigger><SelectValue placeholder="Chọn xe" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="c in cars" :key="c.id" :value="String(c.id)">
                    {{ c.id }} - {{ c.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button :disabled="!canExport || downloading" @click="downloadExcel">
              <Loader2 v-if="downloading" class="mr-2 size-4 animate-spin" />
              <Download v-else class="mr-2 size-4" />
              Xuất Excel
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Xem trước ({{ trips.length }} chuyến)</CardTitle>
        </CardHeader>
        <CardContent class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="whitespace-nowrap">Date</TableHead>
                <TableHead>Itinerary</TableHead>
                <TableHead class="text-right">Km beginning</TableHead>
                <TableHead class="text-right">Km End</TableHead>
                <TableHead class="text-right">Distance</TableHead>
                <TableHead class="text-center">Time Start</TableHead>
                <TableHead class="text-center">Time End</TableHead>
                <TableHead class="text-right">Overtime</TableHead>
                <TableHead class="text-right">Overnight</TableHead>
                <TableHead class="text-right">Toll fee/Air port fee</TableHead>
                <TableHead class="text-right">Holiday</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-if="!trips.length">
                <TableCell colspan="11" class="h-24 text-center text-muted-foreground">
                  Chưa có dữ liệu. Hãy chọn tháng và xe.
                </TableCell>
              </TableRow>

              <TableRow v-for="t in trips" :key="t.id">
                <TableCell class="whitespace-nowrap">{{ t.date }}</TableCell>
                <TableCell class="max-w-[260px] truncate" :title="t.itinerary">{{ t.itinerary }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.odo_start) }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.odo_end) }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.distance) }}</TableCell>
                <TableCell class="text-center">{{ t.time_start ?? '-' }}</TableCell>
                <TableCell class="text-center">{{ t.time_end ?? '-' }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.overtime) }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.overnight) }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.toll_air) }}</TableCell>
                <TableCell class="text-right">{{ fmt(t.holiday) }}</TableCell>
              </TableRow>

              <TableRow v-if="trips.length" class="bg-muted/50 font-semibold">
                <TableCell colspan="4">TOTAL</TableCell>
                <TableCell class="text-right">{{ fmt(totals.distance) }}</TableCell>
                <TableCell colspan="2" />
                <TableCell class="text-right">{{ fmt(totals.overtime) }}</TableCell>
                <TableCell class="text-right">{{ fmt(totals.overnight) }}</TableCell>
                <TableCell class="text-right">{{ fmt(totals.toll_air) }}</TableCell>
                <TableCell class="text-right">{{ fmt(totals.holiday) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
   
</template>