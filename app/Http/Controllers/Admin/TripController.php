<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\TripImage;
use App\Models\TripReopenRequest;
use App\Models\User;
use App\Services\CloudinaryService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TripController extends Controller
{
    public function __construct(
        private readonly CloudinaryService $cloudinary,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        Gate::authorize('viewAny', Trip::class);

        // ---------- 1. Chuẩn hoá & validate tham số lọc ----------
        $filters = $request->validate([
            'search'       => ['nullable', 'string', 'max:100'],
            'status'       => ['nullable', 'in:pending,editing,confirmed,rejected'],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date', 'after_or_equal:from'],
            'is_overnight' => ['nullable', 'in:0,1'],
            'is_holiday'   => ['nullable', 'in:0,1'],
            'advisor_id'   => ['nullable', 'integer', 'exists:users,id'],
            'driver_id'    => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $search    = trim($filters['search'] ?? '');
        $status    = $filters['status'] ?? null;
        $from      = $filters['from'] ?? null;
        $to        = $filters['to'] ?? null;
        $advisorId = $filters['advisor_id'] ?? null;
        $driverId  = $filters['driver_id'] ?? null;

        // array_key_exists + loại chuỗi rỗng — KHÔNG dùng isset()/?: để '0' không bị nuốt
        $overnight = array_key_exists('is_overnight', $filters) && $filters['is_overnight'] !== null && $filters['is_overnight'] !== ''
            ? (int) $filters['is_overnight']
            : null;

        $holiday = array_key_exists('is_holiday', $filters) && $filters['is_holiday'] !== null && $filters['is_holiday'] !== ''
            ? (int) $filters['is_holiday']
            : null;

        // ---------- 2. Query gốc — ADMIN XEM TOÀN BỘ, KHÔNG SCOPE THEO USER ----------
        $query = Trip::with([
                'advisor:id,name',
                'driver:id,name',
                'car:id,license_plate',
                'images',
                'tripExpense',
                'reviewer:id,name',
                'pendingReopenRequest.requester:id,name',
                'latestReopenRequest.requester:id,name',
                'latestReopenRequest.reviewer:id,name',
            ])
            // ----- Tìm kiếm: ID / điểm đi / điểm đến / cố vấn / tài xế -----
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    if (ctype_digit($search)) {
                        $sub->orWhere('id', (int) $search);
                    }

                    $sub->orWhere('origin', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%")
                        ->orWhereHas('advisor', fn ($a) => $a->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('driver', fn ($d) => $d->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($q, $value) => $q->where('status', $value))
            ->when($from, fn ($q, $value) => $q->whereDate('day', '>=', $value))
            ->when($to, fn ($q, $value) => $q->whereDate('day', '<=', $value))
            ->when($advisorId, fn ($q, $value) => $q->where('advisor_id', $value))
            ->when($driverId, fn ($q, $value) => $q->where('driver_id', $value))
            // ----- Phụ phí (quan hệ trip_expense) -----
            ->when(! is_null($overnight), function ($q) use ($overnight) {
                if ($overnight === 1) {
                    $q->whereHas('tripExpense', fn ($e) => $e->where('is_overnight', 1));

                    return;
                }

                // Giá trị 0 HOẶC chưa có bản ghi chi phí
                $q->where(function ($sub) {
                    $sub->whereHas('tripExpense', fn ($e) => $e->where('is_overnight', 0))
                        ->orWhereDoesntHave('tripExpense');
                });
            })
            ->when(! is_null($holiday), function ($q) use ($holiday) {
                if ($holiday === 1) {
                    $q->whereHas('tripExpense', fn ($e) => $e->where('is_holiday', 1));

                    return;
                }

                $q->where(function ($sub) {
                    $sub->whereHas('tripExpense', fn ($e) => $e->where('is_holiday', 0))
                        ->orWhereDoesntHave('tripExpense');
                });
            })
            ->orderByDesc('id')
            ->orderByDesc('day');

        $trips = $query->paginate(10)->withQueryString();

        // ---------- 3. Gắn cờ quyền cho từng dòng ----------
        $trips->getCollection()->transform(function ($trip) use ($user) {
            $trip->setAttribute('can', [
                'update'        => $user->can('update', $trip),
                'delete'        => $user->can('delete', $trip),
                'requestReopen' => $user->can('requestReopen', $trip),
                'review'        => $user->can('review', $trip),
                'reviewReopen'  => $trip->pendingReopenRequest
                    ? $user->can('review', $trip->pendingReopenRequest)
                    : false,
            ]);

            return $trip;
        });

        return Inertia::render('admin/trips/Trip', [
            'trips'     => $trips,
            'tripsMeta' => [
                'total'        => $trips->total(),
                'from'         => $trips->firstItem(),
                'to'           => $trips->lastItem(),
                'current_page' => $trips->currentPage(),
                'last_page'    => $trips->lastPage(),
                'per_page'     => $trips->perPage(),
            ],
            'stats' => [
                'pending'       => Trip::where('status', Trip::STATUS_PENDING)->count(),
                'editing'       => Trip::where('status', Trip::STATUS_EDITING)->count(),
                'pendingReopen' => TripReopenRequest::where('status', 'pending')->count(),
            ],
            'cars'     => Car::where('is_active', 1)->get(['id', 'license_plate']),
            'advisors' => User::role('advisor')->where('is_active', 1)->get(['id', 'name']),
            'drivers'  => User::role('driver')->where('is_active', 1)->get(['id', 'name']),
            'filters'  => [
                'search'       => $search ?: null,
                'status'       => $status,
                'from'         => $from,
                'to'           => $to,
                // trả về chuỗi '1' / '0' / null để khớp v-model select tri-state ở FE
                'is_overnight' => is_null($overnight) ? null : (string) $overnight,
                'is_holiday'   => is_null($holiday) ? null : (string) $holiday,
                'advisor_id'   => $advisorId,
                'driver_id'    => $driverId,
            ],
            'can' => [
                'create' => $user->can('create', Trip::class),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Trip::class);

        $data    = $this->validateData($request);
        $expense = $this->activeExpense();

        $trip = DB::transaction(function () use ($data, $expense) {
            $trip = Trip::create([
                ...$data['trip'],
                'distance'     => $data['distance'],
                'status'       => Trip::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            $tripExpense = TripExpense::create(
                $this->expensePayload($trip->id, $data['expense'], $expense)
            );

            $trip->update(['total_fee' => $this->calculateTotalFee($tripExpense)]);
            $this->syncImages($trip, $data['images']);

            return $trip;
        });

        return redirect()
            ->route('admin.trips.index')
            ->with('success', "Tạo chuyến đi thành công (ID: {$trip->id}, ngày: {$this->formatDate($trip->day)}).");
    }

    public function update(Request $request, Trip $trip)
    {
        Gate::authorize('update', $trip);

        $trip->load('images');

        $data    = $this->validateData($request, $trip);
        $expense = $this->activeExpense();
        $user    = auth()->user();

        // admin / manager được bỏ qua khoá trạng thái; editor vẫn theo policy
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);

        $previousStatus = $trip->status;
        $orphans        = [];

        DB::transaction(function () use ($trip, $data, $expense, $isPrivileged, &$orphans) {
            // Khoá dòng, kiểm tra lại: tránh xung đột confirm/reject xảy ra song song
            $fresh = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            if (! $isPrivileged && $fresh->isLockedForDriver()) {
                abort(409, 'Chuyến đi này đã bị khoá chỉnh sửa. Vui lòng tải lại trang.');
            }

            $trip->update([...$data['trip'], 'distance' => $data['distance']]);

            $tripExpense = TripExpense::updateOrCreate(
                ['trip_id' => $trip->id],
                $this->expensePayload($trip->id, $data['expense'], $expense)
            );

            $trip->update(['total_fee' => $this->calculateTotalFee($tripExpense)]);

            // 1) Xoá ảnh mồ côi khỏi DB
            $orphans = $this->pruneImages($trip, $data);
            // 2) Đồng bộ ảnh mới
            $this->syncImages($trip, $data['images']);

            // Người không có đặc quyền chỉnh sửa -> đưa về trạng thái chờ duyệt
            if (! $isPrivileged) {
                $trip->markSubmitted();
            }
        });

        // 3) Xoá trên Cloudinary SAU KHI transaction thành công
        foreach ($orphans as $publicId) {
            $this->cloudinary->destroy($publicId);
        }

        return redirect()->back()
            ->with('success', $this->updateMessage($trip, $previousStatus, $isPrivileged));
    }

    public function destroy(Trip $trip)
    {
        Gate::authorize('delete', $trip);

        $trip->load('images');
        $publicIds = $trip->images->pluck('public_id')->filter()->all();

        DB::transaction(function () use ($trip) {
            $trip->images()->delete();
            $trip->reopenRequests()->delete();
            TripExpense::where('trip_id', $trip->id)->delete();
            $trip->delete();
        });

        foreach ($publicIds as $publicId) {
            $this->cloudinary->destroy($publicId);
        }

        return redirect()->back(303)->with('success', "Đã xoá chuyến đi (ID: {$trip->id}). Ngày: {$this->formatDate($trip->day)}");
    }

    /* HELPERS
    /** Validate an toàn + chuẩn hoá payload. */
    private function validateData(Request $request, ?Trip $trip = null): array
    {
        $request->merge([
            'is_overnight'  => $request->boolean('is_overnight'),
            'is_holiday'    => $request->boolean('is_holiday'),
            'images_synced' => $request->boolean('images_synced'),
        ]);

        $validated = $request->validate([
            /* ----- Chuyến đi ----- */
            'advisor_id'     => ['required', 'integer', 'min:1', $this->activeUserWithRole('advisor', 'Advisor')],
            'driver_id'      => ['required', 'integer', 'min:1', $this->activeUserWithRole('driver', 'Driver')],
            'day'            => ['required', 'date'],
            'car_id'         => ['required', 'integer', Rule::exists('cars', 'id')->where('is_active', 1)],
            'origin'         => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time'   => [
                'required',
                'date_format:H:i', 'after:departure_time'
                // Nghỉ đêm thì giờ đến có thể nhỏ hơn (qua ngày)
                // Rule::when(! $request->boolean('is_overnight'), ['after:departure_time']),
            ],
            'odo_start'      => ['required', 'integer', 'min:0'],
            'odo_end'        => ['required', 'integer', 'gt:odo_start'],
            'note'           => ['nullable', 'string', 'max:255'],

            /* ----- Chi phí ----- */
            'overtime'     => ['nullable', 'integer', 'min:0', 'max:24'],
            'toll_fee'     => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'airport_fee'  => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'is_overnight' => ['boolean'],
            'is_holiday'   => ['boolean'],

            /* ----- Ảnh ----- */
            'images'              => ['nullable', 'array', 'max:10'],
            'images.*.public_id'  => ['required', 'string', 'max:255'],
            'images.*.url'        => [
                'required', 'url', 'max:500',
                'starts_with:https://res.cloudinary.com/',
            ],
            'images.*.format' => ['nullable', 'string', 'max:20'],
            'images.*.width'  => ['nullable', 'integer', 'min:0'],
            'images.*.height' => ['nullable', 'integer', 'min:0'],
            'images.*.bytes'  => ['nullable', 'integer', 'min:0'],

            // Danh sách ảnh GIỮ LẠI
            'images_synced'    => ['boolean'],
            'kept_image_ids'   => ['nullable', 'array'],
            'kept_image_ids.*' => ['integer'],
        ], [
            'odo_end.gt'                     => 'ODO kết thúc phải lớn hơn ODO bắt đầu.',
            'arrival_time.after'             => 'Giờ đến phải sau giờ đi.',
            'images.max'                     => 'Chỉ được tải lên tối đa 10 ảnh cho mỗi chuyến đi.',
            'images.*.url.starts_with'       => 'Đường dẫn ảnh không hợp lệ.',
        ]);

        return [
            'trip' => [
                'advisor_id'     => $validated['advisor_id'],
                'driver_id'      => $validated['driver_id'],
                'day'            => $validated['day'],
                'car_id'         => $validated['car_id'],
                'origin'         => $validated['origin'],
                'destination'    => $validated['destination'],
                'departure_time' => $validated['departure_time'],
                'arrival_time'   => $validated['arrival_time'],
                'odo_start'      => $validated['odo_start'],
                'odo_end'        => $validated['odo_end'],
                'note'           => $validated['note'] ?? null,
            ],
            'expense' => [
                'overtime'     => (int) ($validated['overtime'] ?? 0),
                'toll_fee'     => (float) ($validated['toll_fee'] ?? 0),
                'airport_fee'  => (float) ($validated['airport_fee'] ?? 0),
                'is_overnight' => (bool) ($validated['is_overnight'] ?? false),
                'is_holiday'   => (bool) ($validated['is_holiday'] ?? false),
            ],
            'distance'       => $validated['odo_end'] - $validated['odo_start'],
            'images'         => $validated['images'] ?? [],
            'images_synced'  => (bool) ($validated['images_synced'] ?? false),
            'kept_image_ids' => array_map('intval', $validated['kept_image_ids'] ?? []),
        ];
    }

    /** Kiểm tra user tồn tại, đúng vai trò và đang hoạt động. */
    private function activeUserWithRole(string $role, string $label): Closure
    {
        return function ($_attribute, $value, $fail) use ($role, $label): void {
            $exists = User::query()
                ->whereKey($value)
                ->where('is_active', 1)
                ->role($role)
                ->exists();

            if (! $exists) {
                $fail("{$label} không hợp lệ hoặc đã bị khoá.");
            }
        };
    }

    private function activeExpense(): Expense
    {
        $expense = Expense::where('is_active', 1)->first();

        if (! $expense) {
            throw ValidationException::withMessages([
                'expense' => 'Chưa có bảng định mức chi phí đang hoạt động. Vui lòng cấu hình Expense.',
            ]);
        }

        return $expense;
    }

    /** Snapshot đơn giá tại thời điểm ghi nhận. */
    private function expensePayload(int $tripId, array $input, Expense $expense): array
    {
        return [
            'trip_id'        => $tripId,
            'expense_id'     => $expense->id,
            'overtime'       => $input['overtime'],
            'overtime_rate'  => $expense->overtime_rate,
            'is_overnight'   => $input['is_overnight'],
            'overnight_rate' => $expense->overnight_rate,
            'is_holiday'     => $input['is_holiday'],
            'holiday_rate'   => $expense->holiday_rate,
            'toll_fee'       => $input['toll_fee'],
            'airport_fee'    => $input['airport_fee'],
        ];
    }

    private function calculateTotalFee(TripExpense $e): float
    {
        return (float) (
            $e->overtime * $e->overtime_rate
            + ($e->is_overnight ? $e->overnight_rate : 0)
            + ($e->is_holiday ? $e->holiday_rate : 0)
            + $e->toll_fee
            + $e->airport_fee
        );
    }

    /** Thêm ảnh mới do client upload. */
    private function syncImages(Trip $trip, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $order = (int) $trip->images()->max('sort_order');

        foreach (array_values($images) as $i => $img) {
            $trip->images()->firstOrCreate(
                ['public_id' => $img['public_id']],
                [
                    'url'        => $img['url'],
                    'format'     => $img['format'] ?? null,
                    'width'      => $img['width'] ?? null,
                    'height'     => $img['height'] ?? null,
                    'bytes'      => $img['bytes'] ?? null,
                    'sort_order' => $order + $i + 1,
                ]
            );
        }
    }

    /**
     * Xoá ảnh không còn trong danh sách giữ lại (chống IDOR: chỉ đụng ảnh của chính chuyến đi).
     * Trả về mảng public_id cần xoá trên Cloudinary.
     */
    private function pruneImages(Trip $trip, array $data): array
    {
        if (! $data['images_synced']) {
            return [];
        }

        $stale = $trip->images()
            ->whereNotIn('id', $data['kept_image_ids'])
            ->get();

        if ($stale->isEmpty()) {
            return [];
        }

        TripImage::whereIn('id', $stale->pluck('id'))->delete();

        return $stale->pluck('public_id')->filter()->all();
    }

    private function updateMessage(Trip $trip, string $previousStatus, bool $isPrivileged): string
    {
        if ($isPrivileged) {
            return "Đã cập nhật chuyến đi (ID: {$trip->id})- Ngày: {$this->formatDate($trip->day)}.";
        }

        return match ($previousStatus) {
            Trip::STATUS_PENDING  => "Đã cập nhật chuyến đi #{$trip->id} - Ngày: {$this->formatDate($trip->day)}. Chuyến đi vẫn đang chờ duyệt.",
            Trip::STATUS_REJECTED => "Đã cập nhật chuyến đi #{$trip->id} - Ngày: {$this->formatDate($trip->day)} sau khi bị từ chối. Chuyến đi được gửi lại cho cố vấn duyệt.",
            Trip::STATUS_EDITING  => "Đã cập nhật chuyến đi #{$trip->id} - Ngày: {$this->formatDate($trip->day)}. Trạng thái chuyển về \"Chờ duyệt\".",
            default               => "Đã cập nhật chuyến đi (ID: {$trip->id}) - Ngày: {$this->formatDate($trip->day)}.",
        };
    }
    private function formatDate(?string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : '';
    }
}