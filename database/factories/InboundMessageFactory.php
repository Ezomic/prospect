<?php

namespace Database\Factories;

use App\Enums\InboundMessageKind;
use App\Models\Company;
use App\Models\InboundMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboundMessage>
 */
class InboundMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'kind' => InboundMessageKind::Reply,
            'from' => fake()->companyEmail(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'message_id' => '<'.fake()->uuid().'@mail.example>',
            'received_at' => now(),
        ];
    }
}
