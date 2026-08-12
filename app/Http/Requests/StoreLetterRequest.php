<?php

namespace App\Http\Requests;

use App\Enums\LetterType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(LetterType::class)],
        ];
    }

    public function letterType(): LetterType
    {
        return LetterType::tryFrom($this->string('type')->toString()) ?? LetterType::OpenAanbod;
    }
}
