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
            'id' => 1,
            'overkm_rate' => 6500,
            'overtime_rate' => 30000,
            'overnight_rate' => 300000,
            'holiday_rate' => 400000,
        ]];

        Expense::insert($listExpenses);
    }
}
