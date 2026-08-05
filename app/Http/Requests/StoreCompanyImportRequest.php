<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyImportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv' => ['required', 'string', 'max:200000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['integer', 'min:2'],
        ];
    }
}
