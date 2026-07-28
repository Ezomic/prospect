<?php

namespace Database\Factories;

use App\Enums\LetterStatus;
use App\Models\Company;
use App\Models\Letter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Letter>
 */
class LetterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraphs(3, true),
            'status' => LetterStatus::Draft,
            'generated_at' => now(),
            'sent_at' => null,
        ];
    }
}
