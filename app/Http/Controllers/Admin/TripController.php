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
        $trips = Trip::latest()
            ->paginate(10);
        $cars = Car::where('is_active', 1)->get();
        $advisors = User::role('advisor')->get();
        $drivers = User::role('driver')->get();

        // dd($advisors);
        // $cars = Car::where('is_active', 1)->get();
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
            'odo_end' => 'required|integer',
            'note'=> 'string|max:255',
            

        ]);
        $distance = $request->input('odo_end') - $request->input('odo_start');
        $validatedExpense = $request->validate([
                'overtime' => 'integer',
                'overnight' => 'integer',
                'toll_fee' => 'integer',
                'airport_fee' => 'integer',
                'holiday'=> 'integer',
            ]);
        $expense = Expense::where('is_active', 1)->first();
        // dd($expense);
        
        return DB::transaction(function () use ($validatedTrip, $validatedExpense, $expense, $distance) {
            $trip = Trip::create($validatedTrip);
            $trip_expense = TripExpense::create([
                'trip_id'=> $trip['id'],
                'expense_id' => $expense['id'],
                'overtime' => $validatedExpense['overtime'],
                'overnight'=> $validatedExpense['overnight'],
                'overtime_rate'=> $expense['overtime_rate'],
                'overnight_rate'=> $expense['overnight_rate'],
                'toll_fee' => $validatedExpense['toll_fee'],
                'airport_fee' => $validatedExpense['airport_fee'],
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
        $validated = $request->validate([
            'name' => 'string|max:255',
            'brand' => 'string|max:255',
            'model' => 'string|max:255',
            'year' => 'integer|min:1900|max:' . date('Y'),
            'license_plate' => 'required|string|max:20|unique:Trips,license_plate,' . $id,
            'owner' => 'string|max:255',
            'status' => 'in:1,0',
        ]);
        Trip::where('id', $id)->update($validated);

        return redirect()->route('admin.Trips.index')->with('success', 'Trip updated successfully');
    }
    public function destroy($id)
    {
        Trip::findOrFail($id)->delete();
        return redirect()->route('admin.Trips.index')->with('success', 'Car deleted successfully');
    }
}
