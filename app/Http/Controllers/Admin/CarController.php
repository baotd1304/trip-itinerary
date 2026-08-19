<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Car;
use Illuminate\Support\Facades\Storage;

class CarController extends Controller
{
    public function index()
    {
        $cars = Car::query()
            ->select('id', 'name', 'brand', 'model', 'year', 'license_plate',
                     'owner', 'is_active')
            ->latest()
            ->paginate(10);
        return Inertia::render('admin/Car', [
            'cars' => $cars,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'string|max:255',
            'model' => 'string|max:255',
            'year' => 'integer|min:1900|max:' . date('Y'),
            'license_plate' => 'required|string|max:20|unique:cars,license_plate',
            'owner' => 'string|max:255',
            'is_active' => 'in:1,0',
        ]);
        Car::create($validated);

        return redirect()->route('admin.cars.index')->with('success', 'Car created successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'brand' => 'string|max:255',
            'model' => 'string|max:255',
            'year' => 'integer|min:1900|max:' . date('Y'),
            'license_plate' => 'required|string|max:20|unique:cars,license_plate,' . $id,
            'owner' => 'string|max:255',
            'is_active' => 'in:1,0',
        ]);
        Car::where('id', $id)->update($validated);

        return redirect()->route('admin.cars.index')->with('success', 'Car updated successfully');
    }
    public function destroy($id)
    {
        Car::findOrFail($id)->delete();
        return redirect()->route('admin.cars.index')->with('success', 'Car deleted successfully');
    }
}
