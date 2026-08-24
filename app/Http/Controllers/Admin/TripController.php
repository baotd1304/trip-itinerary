<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Car;
use App\Models\User;
use App\Models\Expense;
use App\Models\TripExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::with('tripExpense')->latest()
            ->paginate(10);
        $cars = Car::where('is_active', 1)->get();
        $advisors = User::role('advisor')->get();
        $drivers = User::role('driver')->get();
        
        return Inertia::render('admin/Trip', [
            'trips' => $trips,
            'cars' => $cars,
            'advisors' => $advisors,
            'drivers' => $drivers,
        ]);
    }

    public function store(Request $request)
    {
        $validatedTrip = $request->validate([
            'driver'=> 'required|string|max:255',
            'advisor'=> 'required|string|max:255',
            'day' => 'required|date',
            'car_id' => 'required|integer',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_time' => ' before:arrival_time',
            'arrival_time' => ' after:departure_time',
            'odo_start' => 'required|integer',
            'odo_end' => 'required|integer|gt:odo_start',
            'note'=> 'string|max:255',
        ]);
        $distance = $request->input('odo_end') - $request->input('odo_start');
        $validatedExpense = $request->validate([
                'overtime' => 'integer',
                'is_overnight' => 'boolean',
                'is_holiday'=> 'boolean',
                'toll_fee' => 'numeric', 'regex:/^\d{1,15}$/',
                'airport_fee' => 'numeric', 'regex:/^\d{1,15}$/',
            ]);
        $expense = Expense::where('is_active', 1)->first();
        
        return DB::transaction(function () use ($validatedTrip, $validatedExpense, $expense, $distance) {
            $trip = Trip::create($validatedTrip);
            $trip_expense = TripExpense::create([
                'trip_id'=> $trip['id'],
                'expense_id' => $expense['id'],
                'overtime' => $validatedExpense['overtime'],
                'overtime_rate'=> $expense['overtime_rate'],
                'is_overnight'=> $validatedExpense['is_overnight'],
                'overnight_rate'=> $expense['overnight_rate'],
                'toll_fee' => $validatedExpense['toll_fee'],
                'airport_fee' => $validatedExpense['airport_fee'],
                'is_holiday' => $validatedExpense['is_holiday'],
                'holiday_rate' => $expense['holiday_rate'],
            ]);
            $total_fee = $trip_expense['overtime']*$trip_expense['overtime_rate']
                        + $trip_expense['overnight']*$trip_expense['overnight_rate']
                        + $trip_expense['toll_fee']+$trip_expense['airport_fee']+$trip_expense['holiday_rate'];
            $trip->update([
                'total_fee' => $total_fee,
                'distance' => $distance,
            ]);
            
            return redirect()->route('admin.trips.index')->with('success', 'Trip created successfully');
        });

    }

    public function update(Request $request, $id)
    {
        $validatedTrip = $request->validate([
            'driver'=> 'required|string|max:255',
            'advisor'=> 'required|string|max:255',
            'day' => 'required|date',
            'car_id' => 'required|integer',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_time' => ' before:arrival_time',
            'arrival_time' => ' after:departure_time',
            'odo_start' => 'required|integer',
            'odo_end' => 'required|integer|gt:odo_start',
            'note'=> 'string|max:255',
        ]);
        $distance = $request->input('odo_end') - $request->input('odo_start');
        $validatedExpense = $request->validate([
                'overtime' => 'integer',
                'is_overnight' => 'boolean',
                'is_holiday'=> 'boolean',
                'toll_fee' => 'numeric', 'regex:/^\d{1,15}$/',
                'airport_fee' => 'numeric', 'regex:/^\d{1,15}$/',
            ]);
        $expense = Expense::where('is_active', 1)->first();
        return DB::transaction(function () use ($validatedTrip, $validatedExpense, $expense, $distance, $id) {
            $trip = Trip::where('id', $id)->firstOrFail();
            $trip->update($validatedTrip);
            $trip_expense = TripExpense::where('trip_id', $id)->firstOrFail();
            $trip_expense->update([
                'expense_id' => $expense['id'],
                'overtime' => $validatedExpense['overtime'],
                'overtime_rate'=> $expense['overtime_rate'],
                'is_overnight'=> $validatedExpense['is_overnight'],
                'overnight_rate'=> $expense['overnight_rate'],
                'toll_fee' => $validatedExpense['toll_fee'],
                'airport_fee' => $validatedExpense['airport_fee'],
                'is_holiday' => $validatedExpense['is_holiday'],
                'holiday_rate' => $expense['holiday_rate'],
            ]);
            $total_fee = $trip_expense['overtime']      *   $trip_expense['overtime_rate']
                        + $trip_expense['overnight']    *   $trip_expense['overnight_rate']
                        + $trip_expense['toll_fee']     +   $trip_expense['airport_fee']    +   $trip_expense['holiday_rate'];
            $trip->update([
                'total_fee' => $total_fee,
                'distance' => $distance,
            ]);
            
            return redirect()->back()->with('success', 'Trip updated successfully ID: '. $id);
        });
    }
    public function destroy($id)
    {
        Trip::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Trip deleted successfully ID: ' . $id);
    }
}
