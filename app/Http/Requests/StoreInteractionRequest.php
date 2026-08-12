<?php

namespace App\Http\Requests;

use App\Enums\InteractionKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInteractionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(InteractionKind::class)],
            // Logged after the fact, so the past is expected; the future is a
            // typo, not a plan.
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'summary' => ['required', 'string', 'max:2000'],
        ];
    }
}
