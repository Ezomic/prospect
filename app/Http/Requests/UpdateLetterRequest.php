<?php

namespace App\Http\Requests;

use App\Enums\LetterStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterRequest extends FormRequest
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
            // Sent is owned by the sender, never by the form: allowing it here
            // let a letter claim it was sent when no mail ever left.
            'status' => ['required', Rule::enum(LetterStatus::class)->except(LetterStatus::Sent)],
        ];
    }
}
