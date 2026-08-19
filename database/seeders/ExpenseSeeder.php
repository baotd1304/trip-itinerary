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
        // $listExpenses = [[
        //     'id'=> 1,
        //     'overkm_charge' => 6500,
        //     'overtime_charge' => 30000,
        //     'overnight_charge' => 300000,
        //     'holiday_fee' => 400000,
        // ],[
        //     'id'=> 2,
        //     'overkm_charge' => 8000,
        //     'overtime_charge' => 40000,
        //     'overnight_charge' => 400000,
        //     'holiday_fee' => 500000,
        // ]];

        $listExpenses = [[
            'id' => 1,
            'name' => 'Vượt km',
            'unit_price' => 6500,
        ],[
            'id' => 2,
            'name' => 'Tăng ca',
            'unit_price' => 30000,
        ],[
            'id' => 3,
            'name' => 'Qua đêm',
            'unit_price' => 300000,
        ],[
            'id' => 4,
            'name' => 'Phí cầu đường',
            'unit_price' => 0,
        ],[
            'id' => 5,
            'name' => 'Phí sân bay, bến bãi',
            'unit_price' => 0,
        ],[
            'id' => 6,
            'name' => 'Ngày CN, lễ',
            'unit_price' => 400000,
        ]];

        Expense::insert($listExpenses);
    }
}
