<?php

namespace App\Http\Requests;

use App\Enums\CompanyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyRequest extends FormRequest
{
    /**
     * The sorts the list offers, mapped to the column each one orders by. An
     * allowlist because the value ends up in an order by clause.
     */
    public const SORTABLE = [
        'name' => 'name',
        'status' => 'status',
        'lead_score' => 'lead_score',
        'last_contact' => 'last_contact_at',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CompanyStatus::class)],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'missing_email' => ['nullable', 'boolean'],
        ];
    }

    public function sort(): string
    {
        $sort = $this->string('sort')->toString();

        return array_key_exists($sort, self::SORTABLE) ? $sort : 'name';
    }

    /**
     * @return 'asc'|'desc'
     */
    public function direction(): string
    {
        return $this->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
    }
}
