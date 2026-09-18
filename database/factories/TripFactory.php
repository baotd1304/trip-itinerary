<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $car_id = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $status = [Trip::STATUS_PENDING, Trip::STATUS_CONFIRMED, Trip::STATUS_EDITTING, Trip::STATUS_REJECTED];
        return [
            'advisor_id' => User::factory()->create()->assignRole('advisor')->id,
            'driver_id' => User::factory()->create()->assignRole('driver')->id,
            'car_id' => $this->faker->randomElement($car_id),
            'status' => $this->faker->randomElement($status),
            'day' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'departure_time' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'arrival_time' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'odo_start' => $this->faker->numberBetween(1000, 10000),
            'odo_end' => $this->faker->numberBetween(1000, 10000),
            'distance' => $this->faker->numberBetween(10, 1000),
            'origin' => $this->faker->address(),
            'destination' => $this->faker->address(),
        ];
    }
}
