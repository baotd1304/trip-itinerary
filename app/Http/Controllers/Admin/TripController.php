<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\TripImage;

class TripController extends Controller
{
    public function __construct(
        private readonly CloudinaryService $cloudinary,
    ) {}

    public function index()
    {
        $trips = Trip::with(['tripExpense', 'images'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/Trip', [
            'trips'    => $trips,
            'cars'     => Car::where('is_active', 1)->get(),
            'advisors' => User::role('advisor')->get(['id', 'name']),
            'drivers'  => User::role('driver')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data    = $this->validateData($request);
        $expense = $this->activeExpense();

        $trip = DB::transaction(function () use ($data, $expense) {
            $trip = Trip::create([
                ...$data['trip'],
                'distance' => $data['distance'],
                'status'   => 'pending',
            ]);

            $tripExpense = TripExpense::create(
                $this->expensePayload($trip->id, $data['expense'], $expense)
            );

            $trip->update([
                'total_fee' => $this->calculateTotalFee($tripExpense),
            ]);

            $this->syncImages($trip, $data['images']);

            return $trip;
        });

        return redirect()
            ->route('admin.trips.index')
            ->with('success', "Tạo chuyến thành công (ID: {$trip->id}).");
    }

    public function update(Request $request, int $id)
    {
        $trip    = Trip::with('images')->findOrFail($id);
        $data    = $this->validateData($request, $trip);
        $expense = $this->activeExpense();

        $orphans = [];

        DB::transaction(function () use ($trip, $data, $expense, &$orphans) {
            $trip->update([...$data['trip'], 'distance' => $data['distance']]);

            $tripExpense = TripExpense::updateOrCreate(
                ['trip_id' => $trip->id],
                $this->expensePayload($trip->id, $data['expense'], $expense)
            );

            $trip->update(['total_fee' => $this->calculateTotalFee($tripExpense)]);

            // 1) Xoá bản ghi DB của những ảnh không còn được giữ
            $orphans = $this->pruneImages($trip, $data);

            // 2) Thêm ảnh mới
            $this->syncImages($trip, $data['images']);
        });

        // 3) Chỉ gọi Cloudinary SAU KHI transaction đã commit
        foreach ($orphans as $publicId) {
            $this->cloudinary->destroy($publicId);
        }

        return redirect()->back()->with('success', "Cập nhật chuyến thành công (ID: {$trip->id}).");
    }

    public function destroy(int $id)
    {
        $trip      = Trip::with('images')->findOrFail($id);
        $publicIds = $trip->images->pluck('public_id')->all();

        DB::transaction(function () use ($trip) {
            $trip->images()->delete();
            TripExpense::where('trip_id', $trip->id)->delete();
            $trip->delete();
        });

        foreach ($publicIds as $publicId) {
            $this->cloudinary->destroy($publicId);
        }

        return redirect()->back()->with('success', "Đã xoá chuyến (ID: {$id}).");
    }

    /* ===================== Helpers ===================== */

    /**
     * Validate toàn bộ payload (trip + expense + images) và trả về mảng đã chuẩn hoá.
     */
    private function validateData(Request $request, ?Trip $trip = null): array
    {
        $request->merge([
            'is_overnight'  => $request->boolean('is_overnight'),
            'is_holiday'    => $request->boolean('is_holiday'),
            'images_synced' => $request->boolean('images_synced'),
        ]);

        $validated = $request->validate([
            /* ----- Trip ----- */
            'advisor'        => ['required', 'string', 'max:255'],
            'driver'         => ['required', 'string', 'max:255'],
            'day'            => ['required', 'date'],
            'car_id'         => ['required', Rule::exists('cars', 'id')->where('is_active', 1)],
            'origin'         => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time'   => [
                'required',
                'date_format:H:i',
                // Chuyến nghỉ đêm thì giờ đến có thể nhỏ hơn giờ đi (qua ngày hôm sau)
                Rule::when(! $request->boolean('is_overnight'), ['after:departure_time']),
            ],
            'odo_start'      => ['required', 'integer', 'min:0'],
            'odo_end'        => ['required', 'integer', 'gt:odo_start'],
            'note'           => ['nullable', 'string', 'max:255'],

            /* ----- Expense ----- */
            'overtime'     => ['nullable', 'integer', 'min:0', 'max:4'],
            'toll_fee'     => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'airport_fee'  => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'is_overnight' => ['boolean'],
            'is_holiday'   => ['boolean'],

            /* ----- Images (Cloudinary) ----- */
            'images'             => ['nullable', 'array', 'max:10'],
            'images.*.public_id' => ['required', 'string', 'max:255'],
            'images.*.url'       => [
                'required', 'url', 'max:500',
                'starts_with:https://res.cloudinary.com/',
            ],
            'images.*.format' => ['nullable', 'string', 'max:20'],
            'images.*.width'  => ['nullable', 'integer', 'min:0'],
            'images.*.height' => ['nullable', 'integer', 'min:0'],
            'images.*.bytes'  => ['nullable', 'integer', 'min:0'],

            // Danh sách ảnh GIỮ LẠI (thay cho removed_image_ids)
            'images_synced'    => ['nullable', 'boolean'],
            'kept_image_ids'   => ['nullable', 'array'],
            'kept_image_ids.*' => ['integer'],
        ], [
            'odo_end.gt'          => 'Odo kết thúc phải lớn hơn odo bắt đầu.',
            'arrival_time.after'  => 'Giờ đến phải sau giờ đi (trừ chuyến nghỉ đêm).',
            'images.max'          => 'Chỉ được tải lên tối đa 10 ảnh cho mỗi chuyến.',
            'images.*.url.starts_with' => 'Đường dẫn ảnh không hợp lệ.',
        ]);

        return [
            'trip' => [
                'advisor'        => $validated['advisor'],
                'driver'         => $validated['driver'],
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
                'overtime'     => (int)   ($validated['overtime'] ?? 0),
                'toll_fee'     => (float) ($validated['toll_fee'] ?? 0),
                'airport_fee'  => (float) ($validated['airport_fee'] ?? 0),
                'is_overnight' => (bool)  ($validated['is_overnight'] ?? false),
                'is_holiday'   => (bool)  ($validated['is_holiday'] ?? false),
            ],
            'distance'          => $validated['odo_end'] - $validated['odo_start'],
            'images'         => $validated['images'] ?? [],
            'images_synced'  => (bool) ($validated['images_synced'] ?? false),
            'kept_image_ids' => array_map('intval', $validated['kept_image_ids'] ?? []),
        ];
    }

    private function activeExpense(): Expense
    {
        $expense = Expense::where('is_active', 1)->first();

        if (! $expense) {
            throw ValidationException::withMessages([
                'overtime' => 'Chưa cấu hình bảng giá đang hoạt động. Vui lòng kiểm tra mục Expense.',
            ]);
        }

        return $expense;
    }

    /**
     * Snapshot đơn giá tại thời điểm ghi nhận, tránh việc đổi bảng giá làm sai số liệu cũ.
     */
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
            + ($e->is_holiday   ? $e->holiday_rate   : 0)
            + $e->toll_fee
            + $e->airport_fee
        );
    }

    /**
     * Lưu metadata ảnh đã upload trực tiếp lên Cloudinary từ phía client.
     */
    private function syncImages(Trip $trip, array $images): void
    {
        if (! $images) {
            return;
        }

        $order = (int) $trip->images()->max('sort_order');

        foreach (array_values($images) as $i => $img) {
            $trip->images()->firstOrCreate(
                ['public_id' => $img['public_id']],
                [
                    'url'        => $img['url'],
                    'format'     => $img['format'] ?? null,
                    'width'      => $img['width']  ?? null,
                    'height'     => $img['height'] ?? null,
                    'bytes'      => $img['bytes']  ?? null,
                    'sort_order' => $order + $i + 1,
                ]
            );
        }
    }

    /**
     * Chỉ xoá ảnh thuộc đúng chuyến này (tránh IDOR).
     */
    /**
     * Xoá mọi ảnh KHÔNG nằm trong danh sách giữ lại.
     * kept_image_ids rỗng  => xoá toàn bộ ảnh của chuyến.
     * Trả về danh sách public_id cần dọn trên Cloudinary.
     */
    private function pruneImages(Trip $trip, array $data): array
    {
        // Form không quản lý ảnh => không đụng tới
        if (! $data['images_synced']) {
            return [];
        }

        // whereNotIn với mảng rỗng => Laravel sinh "1 = 1" => lấy tất cả. Đúng ý đồ.
        $stale = $trip->images()
            ->whereNotIn('id', $data['kept_image_ids'])
            ->get();

        if ($stale->isEmpty()) {
            return [];
        }

        TripImage::whereIn('id', $stale->pluck('id'))->delete();

        return $stale->pluck('public_id')->all();
    }

}