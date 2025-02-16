<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'manager' => User::factory(),
            'price' => fake()->randomFloat(2, 0, 100),
            'image' => fake()->imageUrl(),
            'youtube_url' => fake()->url(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
