<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Car;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listCars = [[
            'name' => 'Car so 1',
            'license_plate' => '73K1-1234',
        ],[
            'name' => 'Car so 2',
            'license_plate' => '73E1-1214',
        ],[
            'name' => 'Car so 3',
            'license_plate' => '73G1-44646',
        ],[
            'name' => 'Car so 4',
            'license_plate' => '73U1-5895',
        ],[
            'name' => 'Car so 5',
            'license_plate' => '73T1-8647',
        ],[
            'name' => 'Car so 6',
            'license_plate' => '73D1-1354',
        ]];

        Car::insert($listCars);
    }
}
