<?php

namespace App\Http\Requests\Settings;

use App\Enums\LetterLanguage;
use App\Enums\LetterType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterTemplateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'email_subject' => ['required', 'string', 'max:255'],
            'email_body' => ['required', 'string'],
            'type' => ['nullable', Rule::enum(LetterType::class)],
            'language' => ['nullable', Rule::enum(LetterLanguage::class)],
        ];
    }

    public function letterLanguage(): LetterLanguage
    {
        return LetterLanguage::tryFrom($this->string('language')->toString()) ?? LetterLanguage::Dutch;
    }

    public function letterType(): LetterType
    {
        return LetterType::tryFrom($this->string('type')->toString()) ?? LetterType::OpenAanbod;
    }
}
