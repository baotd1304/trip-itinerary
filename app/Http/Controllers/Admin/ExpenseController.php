<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expense;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::query()
            
            ->latest()
            ->paginate(10);
        return Inertia::render('admin/Expense', [
            'expenses' => $expenses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            
        ]);
        Expense::create($validated);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense created successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
           
        ]);
        Expense::where('id', $id)->update($validated);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated successfully');
    }
    public function destroy($id)
    {
        Expense::findOrFail($id)->delete();
        return redirect()->route('admin.expenses.index')->with('success', 'Expense deleted successfully');
    }
}
