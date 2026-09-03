<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('client/Home', [
            // 'latestTrips' => Trip::query()
            //     ->latest()
            //     ->take(6)
            //     ->get()
            //     ->map(fn (Trip $trip) => [
            //         'id'            => $trip->id,
            //         'name'          => $trip->name,
            //         'destination'   => $trip->destination,
            //         'start_date'    => $trip->start_date,
            //         'end_date'      => $trip->end_date,
            //         'status'        => $trip->status,
            //         'total_expense' => $trip->expenses()->sum('amount'),
            //     ]),

            // 'stats' => [
            //     'trips'   => Trip::count(),
            //     'cars'    => Car::count(),
            //     'members' => User::count(),
            // ],
        ]);
    }
}