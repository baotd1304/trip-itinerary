<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Expense;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listExpenses = [[
            'overkm_charge' => 6500,
            'overtime_charge' => 30000,
            'overnight_charge' => 300000,
            'holiday_fee' => 400000,
        ],[
            'overkm_charge' => 8000,
            'overtime_charge' => 40000,
            'overnight_charge' => 400000,
            'holiday_fee' => 500000,
        ]];
        Expense::insert($listExpenses);
    }
}
