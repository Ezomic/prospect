<?php

namespace App\Http\Requests;

use App\Enums\CompanyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCompanyActionRequest extends FormRequest
{
    public const ACTIONS = ['status', 'do_not_contact', 'clear_follow_up', 'generate_letter'];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Explicit ids only. A select-all-across-pages would act on rows
            // the user cannot see, which is how bulk tools cause damage.
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:companies,id'],
            'action' => ['required', Rule::in(self::ACTIONS)],
            'status' => ['required_if:action,status', Rule::enum(CompanyStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        $ids = [];

        foreach ((array) $this->validated('ids') as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }
}
