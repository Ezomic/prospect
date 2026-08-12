<?php

namespace Database\Factories;

use App\Enums\InteractionKind;
use App\Models\Company;
use App\Models\Interaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaction>
 */
class InteractionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'kind' => InteractionKind::Call,
            'occurred_at' => now(),
            'summary' => fake()->sentence(8),
        ];
    }
}
