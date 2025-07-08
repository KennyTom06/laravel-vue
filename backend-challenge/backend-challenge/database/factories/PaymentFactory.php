<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Payment;
use App\Models\User;
use App\Models\Amortization;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = ['pending', 'paid'];
        
        return [
            'amount' => $this->faker->numberBetween(10000, 5000000), // 10K to 5M in smallest currency unit
            'state' => $this->faker->randomElement($states),
            'user_id' => User::factory(),
            'amortization_id' => Amortization::factory(),
        ];
    }

    /**
     * Indicate that the payment is pending.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => 'pending',
        ]);
    }

    /**
     * Indicate that the payment is paid.
     *
     * @return static
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => 'paid',
        ]);
    }

    /**
     * Create a payment for a specific amortization.
     *
     * @param int $amortizationId
     * @return static
     */
    public function forAmortization(int $amortizationId): static
    {
        return $this->state(fn (array $attributes) => [
            'amortization_id' => $amortizationId,
        ]);
    }

    /**
     * Create a payment for a specific user.
     *
     * @param int $userId
     * @return static
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a payment with specific amount.
     *
     * @param int $amount
     * @return static
     */
    public function withAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
