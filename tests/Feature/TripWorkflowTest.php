<?php

use App\Models\Trip;
use App\Models\TripReopenRequest;
use App\Models\User;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('driver', 'web');
    Role::findOrCreate('advisor', 'web');

    $this->driver = User::factory()
        ->create(['is_active' => true]);

    $this->driver->assignRole('driver');

    $this->advisor = User::factory()
        ->create(['is_active' => true]);

    $this->advisor->assignRole('advisor');
});

function makeTrip(string $status): Trip
{
    $car = Car::factory()->create();

    return Trip::factory()->create([
        'driver_id' => test()->driver->id,
        'advisor_id' => test()->advisor->id,
        'car_id'    => $car->id,
        'status'    => $status,
    ]);
}

it('driver không sửa được chuyến confirmed', function () {
    $trip = makeTrip(Trip::STATUS_CONFIRMED);

    expect($this->driver->can('update', $trip))
        ->toBeFalse();
});

it('driver gửi được yêu cầu reopen cho chuyến confirmed', function () {
    $trip = makeTrip(Trip::STATUS_CONFIRMED);

    $this->actingAs($this->driver)
        ->post(
            route('client.trips.reopen-requests.store', $trip),
            [
                'reason' => 'Nhập sai odo kết thúc, cần sửa lại số liệu.',
            ]
        )
        ->assertStatus(303);

    expect($trip->refresh()->status)
        ->toBe(Trip::STATUS_CONFIRMED);

    expect($trip->pendingReopenRequest)
        ->not->toBeNull();
});

it('không tạo được 2 yêu cầu reopen cùng lúc', function () {
    $trip = makeTrip(Trip::STATUS_CONFIRMED);

    TripReopenRequest::factory()->create([
        'trip_id'     => $trip->id,
        'requested_by' => $this->driver->id,
        'status'      => TripReopenRequest::STATUS_PENDING,
    ]);

    $this->actingAs($this->driver)
        ->post(
            route('client.trips.reopen-requests.store', $trip),
            [
                'reason' => 'Lý do thứ hai abc',
            ]
        )
        ->assertForbidden();
});

it('advisor duyệt yêu cầu thì trip chuyển sang editing', function () {
    $trip = makeTrip(Trip::STATUS_CONFIRMED);

    $request = TripReopenRequest::factory()->create([
        'trip_id'      => $trip->id,
        'requested_by' => $this->driver->id,
        'status'       => TripReopenRequest::STATUS_PENDING,
    ]);

    $this->actingAs($this->advisor)
        ->patch(
            route('client.reopen-requests.approve', $request)
        )
        ->assertStatus(303);

    expect($trip->refresh()->status)
        ->toBe(Trip::STATUS_EDITING);

    expect($request->refresh()->status)
        ->toBe(TripReopenRequest::STATUS_APPROVED);

    expect($this->driver->can('update', $trip))
        ->toBeTrue();
});

it('advisor từ chối yêu cầu thì trip vẫn confirmed', function () {
    $trip = makeTrip(Trip::STATUS_CONFIRMED);

    $request = TripReopenRequest::factory()->create([
        'trip_id'      => $trip->id,
        'requested_by' => $this->driver->id,
        'status'       => TripReopenRequest::STATUS_PENDING,
    ]);

    $this->actingAs($this->advisor)
        ->patch(
            route('client.reopen-requests.reject', $request),
            [
                'review_note' => 'Số liệu đã đối soát xong.',
            ]
        )
        ->assertStatus(303);

    expect($trip->refresh()->status)
        ->toBe(Trip::STATUS_CONFIRMED);
});

it('driver sửa chuyến editing thì trip quay về pending', function () {
    $trip = makeTrip(Trip::STATUS_EDITING);

    $this->actingAs($this->driver)
        ->put(
            route('client.trips.update', $trip),
            tripPayload($trip)
        )
        ->assertRedirect();

    expect($trip->refresh()->status)
        ->toBe(Trip::STATUS_PENDING);
});

it('driver sửa chuyến rejected thì trip quay về pending và xoá lý do từ chối', function () {
    $trip = makeTrip(Trip::STATUS_REJECTED);

    $trip->update([
        'reject_reason' => 'Sai odo',
    ]);

    $this->actingAs($this->driver)
        ->put(
            route('client.trips.update', $trip),
            tripPayload($trip)
        )
        ->assertRedirect();

    $trip->refresh();

    expect($trip->status)
        ->toBe(Trip::STATUS_PENDING);

    expect($trip->reject_reason)
        ->toBeNull();
});

it('driver chỉ xoá được chuyến pending', function () {
    expect($this->driver->can(
        'delete',
        makeTrip(Trip::STATUS_PENDING)
    ))->toBeTrue();

    expect($this->driver->can(
        'delete',
        makeTrip(Trip::STATUS_EDITING)
    ))->toBeFalse();

    expect($this->driver->can(
        'delete',
        makeTrip(Trip::STATUS_REJECTED)
    ))->toBeFalse();

    expect($this->driver->can(
        'delete',
        makeTrip(Trip::STATUS_CONFIRMED)
    ))->toBeFalse();
});

it('driver sửa được chuyến pending và chuyến vẫn ở pending', function () {
    $trip = makeTrip(Trip::STATUS_PENDING);

    expect($this->driver->can('update', $trip))->toBeTrue();

    $this->actingAs($this->driver)
        ->put(route('client.trips.update', $trip), [...tripPayload($trip), 'origin' => 'Kho Bình Dương'])
        ->assertRedirect();

    expect($trip->refresh())
        ->status->toBe(Trip::STATUS_PENDING)
        ->origin->toBe('Kho Bình Dương');
});

it('driver sửa được pending, editing, rejected nhưng không sửa được confirmed', function () {
    expect($this->driver->can('update', makeTrip(Trip::STATUS_PENDING)))->toBeTrue()
        ->and($this->driver->can('update', makeTrip(Trip::STATUS_EDITING)))->toBeTrue()
        ->and($this->driver->can('update', makeTrip(Trip::STATUS_REJECTED)))->toBeTrue()
        ->and($this->driver->can('update', makeTrip(Trip::STATUS_CONFIRMED)))->toBeFalse();
});

it('sửa chuyến pending sẽ làm mới submitted_at', function () {
    $trip = makeTrip(Trip::STATUS_PENDING);
    $trip->update(['submitted_at' => now()->subDays(2)]);
    $old = $trip->submitted_at;

    $this->actingAs($this->driver)
        ->put(route('client.trips.update', $trip), tripPayload($trip));

    expect($trip->refresh()->submitted_at->gt($old))->toBeTrue();
});

it('trả về 409 khi advisor vừa confirm lúc driver đang lưu', function () {
    $trip = makeTrip(Trip::STATUS_PENDING);

    // Mô phỏng: advisor confirm ngay trước khi request update chạm DB
    $trip->markConfirmed($this->advisor);

    $this->actingAs($this->driver)
        ->put(route('client.trips.update', $trip), tripPayload($trip))
        ->assertForbidden();   // Policy chặn trước, vì trip đã confirmed
});