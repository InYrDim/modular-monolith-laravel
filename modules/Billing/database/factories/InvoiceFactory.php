<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\Invoice;

/**
 * @extends Factory<Invoice>
 *
 * An invoice stores the owning user's id only; Billing never loads Identity
 * models, so the owner is assigned explicitly with {@see self::forUser()}.
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'amount_cents' => fake()->numberBetween(1_000, 500_000),
            'status' => fake()->randomElement(['draft', 'paid']),
            'issued_at' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes): array => ['user_id' => $userId]);
    }
}
