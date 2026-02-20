<?php

namespace Database\Factories;

use App\Models\PersonalFundEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PersonalFundEntry>
 */
class PersonalFundEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'direction' => fake()->randomElement([
                PersonalFundEntry::DIRECTION_CREDIT,
                PersonalFundEntry::DIRECTION_DEBIT,
            ]),
            'amount' => fake()->randomFloat(2, 5, 500),
            'description' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'created_by_user_id' => fn (array $attributes) => $attributes['user_id'],
        ];
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => PersonalFundEntry::DIRECTION_CREDIT,
        ]);
    }

    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => PersonalFundEntry::DIRECTION_DEBIT,
        ]);
    }
}
