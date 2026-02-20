<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]);

        return [
            'type' => $type,
            'amount' => fake()->randomFloat(2, 5, 1000),
            'paid_by_user_id' => User::factory(),
            'description' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'created_by_user_id' => fn (array $attributes) => $attributes['paid_by_user_id'],
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_INCOME,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_EXPENSE,
        ]);
    }
}
