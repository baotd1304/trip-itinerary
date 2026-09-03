<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastname = ["Trần", "Nguyễn", "Lê", "Phan", "Phạm", "Võ"];
        $surname = ["Duy", "Văn", "Huy", "Minh", "Quốc", "Công"];
        $firstname = ["Long", "Hùng", "Hoàng", "Trường", "Đạt", "Bảo"];
        return [
            'name' => fake()->randomElement($lastname)
                    .' '.fake()->randomElement($surname)
                    .' '.fake()->randomElement($firstname),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static {}

    public function advisor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('advisor');
        });
    }

    public function driver(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('driver');
        });
    }
    public function editor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('editor');
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    
}
