<?php

namespace App\Http\Controllers;

use App\Exports\TripsExport;
use App\Http\Requests\ExportTripRequest;
use App\Models\Car;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class TripExportController extends Controller
{
    public function index(Request $request): Response
    {
        $month  = $request->string('month')->toString() ?: now()->format('Y-m');
        $carId  = $request->integer('car_id') ?: null;

        $cars = Car::query()->orderBy('id')->get()
            ->map(fn ($car) => [
                'id'    => $car->id,
                'label' => $car->license_plate ?? $car->name ?? "Xe #{$car->id}",
            ]);

        $preview = [];

        if ($carId) {
            $preview = Trip::query()
                ->with('tripExpense')
                ->confirmed()
                ->ofCar($carId)
                ->ofMonth($month)
                ->orderBy('day')
                ->orderBy('departure_time')
                ->get()
                ->map(function (Trip $trip) {
                    $exp = $trip->tripExpense;

                    return [
                        'id'         => $trip->id,
                        'date'       => optional($trip->day)->format('d/m/Y'),
                        'itinerary'  => trim(($trip->origin ?? '').' - '.($trip->destination ?? ''), ' -'),
                        'odo_start'  => (int) $trip->odo_start,
                        'odo_end'    => (int) $trip->odo_end,
                        'distance'   => (int) $trip->distance,
                        'time_start' => $trip->departure_time ? Carbon::parse($trip->departure_time)->format('H:i') : null,
                        'time_end'   => $trip->arrival_time ? Carbon::parse($trip->arrival_time)->format('H:i') : null,
                        'overtime'   => (float) (($exp?->overtime ?? 0) * ($exp?->overtime_rate ?? 0)),
                        'overnight'  => $exp?->is_overnight ? (float) ($exp->overnight_rate ?? 0) : 0,
                        'toll_air'   => (float) (($exp?->toll_fee ?? 0) + ($exp?->airport_fee ?? 0)),
                        'holiday'    => $exp?->is_holiday ? (float) ($exp->holiday_rate ?? 0) : 0,
                    ];
                })
                ->values();
        }

        return Inertia::render('trips/Export', [
            'cars'    => $cars,
            'filters' => ['month' => $month, 'car_id' => $carId],
            'trips'   => $preview,
        ]);
    }

    public function export(ExportTripRequest $request)
    {
        ['month' => $month, 'car_id' => $carId] = $request->validated();

        $car   = Car::findOrFail($carId);
        $label = Str::slug($car->license_plate ?? $car->name ?? "car-{$car->id}");

        $fileName = sprintf(
            'trips_%s_%s.xlsx',
            $label,
            Carbon::createFromFormat('Y-m', $month)->format('Y_m')
        );

        return Excel::download(new TripsExport($month, (int) $carId), $fileName);
    }
}