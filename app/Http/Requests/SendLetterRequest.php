<?php

namespace App\Http\Requests;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

class SendLetterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // A moment in the past is not a schedule, it is a send. Callers
            // that want to send now simply omit this.
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function scheduledFor(): ?CarbonInterface
    {
        $value = $this->string('scheduled_for')->toString();

        return $value === '' ? null : Date::parse($value);
    }
}
