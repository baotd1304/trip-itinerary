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
        $firstMonth = CarbonImmutable::now()->startOfMonth()->subMonths(11);
        $lastMonth = $firstMonth->addMonths(11);
        $months = collect(range(0, 11))
            ->map(fn (int $offset): string => $firstMonth->addMonths($offset)->format('Y-m'))
            ->values();

        $monthlyStats = $months->mapWithKeys(fn (string $month): array => [
            $month => [
                'month' => $month,
                'tripCount' => 0,
                'expenseTotal' => 0,
            ],
        ])->all();

        $carDistances = [];

        $aggregates = DB::table('trips')
            ->join('cars', 'cars.id', '=', 'trips.car_id')
            ->where('trips.status', Trip::STATUS_CONFIRMED)
            ->whereBetween('trips.day', [
                $firstMonth->toDateString(),
                $lastMonth->endOfMonth()->toDateString(),
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
            $carDistances[$carId] ??= [
                'carId' => $carId,
                'licensePlate' => $aggregate->license_plate,
                'distances' => array_fill(0, $months->count(), 0),
            ];

            $monthIndex = $months->search($month);
            $carDistances[$carId]['distances'][$monthIndex] += (int) $aggregate->distance_total;
        }

        $carDistanceSeries = array_values($carDistances);
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

        return Inertia::render('admin/Dashboard', [
            'monthlyStats' => array_values($monthlyStats),
            'carDistanceSeries' => $carDistanceSeries,
            'distanceAlerts' => $alerts,
            'summary' => [
                'trips' => array_sum(array_column($monthlyStats, 'tripCount')),
                'expenses' => array_sum(array_column($monthlyStats, 'expenseTotal')),
                'distance' => array_sum(array_map(
                    fn (array $car): int => array_sum($car['distances']),
                    $carDistanceSeries,
                )),
                'alerts' => count($alerts),
            ],
            'distanceWarningThreshold' => self::DISTANCE_WARNING_THRESHOLD,
        ]);
    }
}
