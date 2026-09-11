<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Car;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
   public function index()
    {
        $user = auth()->user();
        $query = Trip::with(['tripExpense', 'images']);
        // Lấy trip mà user là driver HOẶC advisor
        if ($user) {
            $query->where(function($q) use ($user) {
                $q->where('driver', $user->name)
                ->orWhere('advisor', $user->name);
            });
        }
        
        $trips = $query->latest()
            ->limit(5);

        return Inertia::render('client/Home', [
            'trips'    => $trips,
            'cars'     => Car::where('is_active', 1)->get(),
            'advisors' => User::role('advisor')->get(['id', 'name']),
            'drivers'  => User::role('driver')->get(['id', 'name']),
        ]);
    }
}