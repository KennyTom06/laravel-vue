<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Amortization;
use App\Models\Project;
use App\Models\Promoter;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Amortization>
 */
class AmortizationFactory extends Factory
{
     /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Amortization::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = ['pending', 'paid'];
        
        return [
            'amount' => $this->faker->numberBetween(1000000, 50000000), // 1M to 50M in smallest currency unit
            'state' => $this->faker->randomElement($states),
            'schedule_date' => $this->faker->dateTimeBetween('-6 months', '+1 year')->format('Y-m-d'),
            'promoter_id' => Promoter::factory(),
            'project_id' => Project::factory(),
        ];
    }

    /**
     * Indicate that the amortization is pending.
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
     * Indicate that the amortization is paid.
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
     * Indicate that the amortization is scheduled for past date (delayed).
     *
     * @return static
     */
    public function delayed(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_date' => $this->faker->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d'),
            'state' => 'pending',
        ]);
    }

    /**
     * Indicate that the amortization is scheduled for future date.
     *
     * @return static
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_date' => $this->faker->dateTimeBetween('+1 day', '+1 year')->format('Y-m-d'),
            'state' => 'pending',
        ]);
    }
}
