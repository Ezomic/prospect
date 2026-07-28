<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'website' => fake()->domainName(),
            'email' => fake()->companyEmail(),
            'contact_name' => fake()->name(),
            'city' => fake()->city(),
            'kvk_number' => (string) fake()->numberBetween(10000000, 99999999),
            'industry' => fake()->randomElement(['Software', 'Consultancy', 'E-commerce', 'Finance', 'Healthcare', 'Education']),
            'status' => fake()->randomElement(CompanyStatus::cases()),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
