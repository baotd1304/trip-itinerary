<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const DISTANCE_WARNING_THRESHOLD = 4300;

    public function __invoke(): Response
    {
        $now = CarbonImmutable::now();
        $currentMonth = $now->startOfMonth();
        $firstMonth = $now->startOfYear()->subYears(4);
        $months = collect();

        for ($month = $firstMonth; $month <= $currentMonth; $month = $month->addMonth()) {
            $months->push($month->format('Y-m'));
        }

        $monthlyStats = $months->mapWithKeys(fn (string $month): array => [
            $month => [
                'month' => $month,
                'tripCount' => 0,
                'expenseTotal' => 0,
            ],
        ])->all();

        $monthIndexes = array_flip($months->all());
        $carStats = [];

        $aggregates = DB::table('trips')
            ->join('cars', 'cars.id', '=', 'trips.car_id')
            ->where('trips.status', Trip::STATUS_CONFIRMED)
            ->whereBetween('trips.day', [
                $firstMonth->toDateString(),
                $now->endOfMonth()->toDateString(),
            ])
            ->select('trips.day', 'trips.car_id', 'cars.license_plate')
            ->selectRaw('COUNT(*) AS trip_count')
            ->selectRaw('COALESCE(SUM(trips.total_fee), 0) AS expense_total')
            ->selectRaw('COALESCE(SUM(trips.distance), 0) AS distance_total')
            ->groupBy('trips.day', 'trips.car_id', 'cars.license_plate')
            ->get();

        foreach ($aggregates as $aggregate) {
            $month = CarbonImmutable::parse($aggregate->day)->format('Y-m');
            $monthlyStats[$month]['tripCount'] += (int) $aggregate->trip_count;
            $monthlyStats[$month]['expenseTotal'] += (float) $aggregate->expense_total;

            $carId = (int) $aggregate->car_id;
            $carStats[$carId] ??= [
                'carId' => $carId,
                'licensePlate' => $aggregate->license_plate,
                'distances' => array_fill(0, $months->count(), 0),
                'tripCounts' => array_fill(0, $months->count(), 0),
                'expenseTotals' => array_fill(0, $months->count(), 0),
            ];

            $monthIndex = $monthIndexes[$month];
            $carStats[$carId]['distances'][$monthIndex] += (int) $aggregate->distance_total;
            $carStats[$carId]['tripCounts'][$monthIndex] += (int) $aggregate->trip_count;
            $carStats[$carId]['expenseTotals'][$monthIndex] += (float) $aggregate->expense_total;
        }

        $carDistanceSeries = array_values($carStats);
        $alerts = [];

        foreach ($carDistanceSeries as $car) {
            foreach ($car['distances'] as $monthIndex => $distance) {
                if ($distance > self::DISTANCE_WARNING_THRESHOLD) {
                    $alerts[] = [
                        'carId' => $car['carId'],
                        'licensePlate' => $car['licensePlate'],
                        'month' => $months[$monthIndex],
                        'distance' => $distance,
                    ];
                }
            }
        }

        usort($alerts, fn (array $left, array $right): int => $right['distance'] <=> $left['distance']);

        $currentMonthKey = $currentMonth->format('Y-m');
        $currentMonthIndex = $months->count() - 1;
        $currentMonthStats = $monthlyStats[$currentMonthKey];

        return Inertia::render('admin/Dashboard', [
            'monthlyStats' => array_values($monthlyStats),
            'carDistanceSeries' => $carDistanceSeries,
            'distanceAlerts' => $alerts,
            'currentMonth' => $currentMonthKey,
            'summary' => [
                'trips' => $currentMonthStats['tripCount'],
                'expenses' => $currentMonthStats['expenseTotal'],
                'distance' => array_sum(array_map(
                    fn (array $car): int => $car['distances'][$currentMonthIndex],
                    $carDistanceSeries,
                )),
                'alerts' => count(array_filter(
                    $alerts,
                    fn (array $alert): bool => $alert['month'] === $currentMonthKey,
                )),
            ],
            'distanceWarningThreshold' => self::DISTANCE_WARNING_THRESHOLD,
        ]);
    }
}
