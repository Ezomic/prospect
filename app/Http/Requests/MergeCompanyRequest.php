<?php

namespace App\Http\Requests;

use App\Actions\Companies\MergeCompanies;
use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeCompanyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'duplicate_id' => [
                'required',
                'integer',
                'exists:companies,id',
                // Merging a company into itself would delete it.
                Rule::notIn([$this->survivorId()]),
            ],
            'take_from_duplicate' => ['nullable', 'array'],
            'take_from_duplicate.*' => [Rule::in(MergeCompanies::FIELDS)],
        ];
    }

    private function survivorId(): ?int
    {
        $company = $this->route('company');

        return $company instanceof Company ? $company->id : null;
    }

    /**
     * @return list<string>
     */
    public function takeFromDuplicate(): array
    {
        $fields = [];

        foreach ((array) ($this->validated('take_from_duplicate') ?? []) as $field) {
            if (is_string($field)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
