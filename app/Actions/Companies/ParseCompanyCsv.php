<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Turns a pasted CSV into rows ready for preview: the mapped values, any
 * validation errors, and whether the row looks like a company we already have
 * (or one listed earlier in the same paste). Nothing is written here, so the
 * preview and the import can parse the same input and agree on the result.
 */
class ParseCompanyCsv
{
    /**
     * The columns an import may set, keyed by the header it is recognised by.
     * Headers are matched loosely, so "Contact name", "contact_name" and
     * "CONTACT NAME" all land on the same column.
     */
    private const COLUMNS = [
        'name',
        'website',
        'email',
        'contact_name',
        'contact_role',
        'city',
        'kvk_number',
        'industry',
        'source',
    ];

    /**
     * @return array{
     *     rows: list<array{line: int, values: array<string, string|null>, errors: array<string, string>, duplicate: array{name: string, matched_on: string}|null}>,
     *     recognised: list<string>,
     *     ignored: list<string>,
     * }
     */
    public function handle(string $csv): array
    {
        $lines = $this->lines($csv);

        if ($lines === []) {
            return ['rows' => [], 'recognised' => [], 'ignored' => []];
        }

        $delimiter = $this->delimiter($lines[0]);
        $header = $this->header($lines[0], $delimiter);

        $rows = [];
        $seen = [];

        foreach (array_slice($lines, 1) as $offset => $line) {
            $values = $this->values($line, $delimiter, $header['recognised']);

            $rows[] = [
                // +2: the header is line one and the offset is zero based.
                'line' => $offset + 2,
                'values' => $values,
                'errors' => $this->errors($values),
                'duplicate' => $this->duplicate($values, $seen),
            ];

            $this->remember($values, $seen);
        }

        return [
            'rows' => $rows,
            'recognised' => array_values(array_unique(array_values($header['recognised']))),
            'ignored' => $header['ignored'],
        ];
    }

    /**
     * @return list<string>
     */
    private function lines(string $csv): array
    {
        $lines = preg_split('/\R/', trim($csv)) ?: [];

        return array_values(array_filter($lines, fn (string $line) => trim($line) !== ''));
    }

    /**
     * Dutch Excel writes semicolons, so pick whichever separator the header
     * actually uses rather than assuming a comma.
     */
    private function delimiter(string $header): string
    {
        return substr_count($header, ';') > substr_count($header, ',') ? ';' : ',';
    }

    /**
     * @return array{recognised: array<int, string>, ignored: list<string>}
     */
    private function header(string $line, string $delimiter): array
    {
        $recognised = [];
        $ignored = [];

        foreach (str_getcsv($line, $delimiter, '"', '\\') as $index => $heading) {
            $key = Str::snake(Str::lower(trim((string) $heading)));
            $key = str_replace([' ', '-'], '_', $key);

            if (in_array($key, self::COLUMNS, true)) {
                $recognised[$index] = $key;

                continue;
            }

            if (trim((string) $heading) !== '') {
                $ignored[] = trim((string) $heading);
            }
        }

        return ['recognised' => $recognised, 'ignored' => $ignored];
    }

    /**
     * @param  array<int, string>  $recognised
     * @return array<string, string|null>
     */
    private function values(string $line, string $delimiter, array $recognised): array
    {
        $cells = str_getcsv($line, $delimiter, '"', '\\');

        $values = array_fill_keys(self::COLUMNS, null);

        foreach ($recognised as $index => $column) {
            $value = trim((string) ($cells[$index] ?? ''));

            $values[$column] = $value === '' ? null : $value;
        }

        return $values;
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array<string, string>
     */
    private function errors(array $values): array
    {
        $validator = Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'kvk_number' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $errors = [];

        foreach ($validator->errors()->toArray() as $field => $messages) {
            $errors[$field] = (string) ($messages[0] ?? '');
        }

        return $errors;
    }

    /**
     * @param  array<string, string|null>  $values
     * @param  array<string, array<string, string>>  $seen
     * @return array{name: string, matched_on: string}|null
     */
    private function duplicate(array $values, array $seen): ?array
    {
        foreach (['email', 'kvk_number', 'name'] as $field) {
            $value = $values[$field] ?? null;

            if ($value === null) {
                continue;
            }

            $key = Str::lower($value);

            if (isset($seen[$field][$key])) {
                return ['name' => $seen[$field][$key], 'matched_on' => "{$field} (earlier in this paste)"];
            }

            $existing = Company::query()->whereRaw("lower({$field}) = ?", [$key])->first(['id', 'name']);

            if ($existing !== null) {
                return ['name' => $existing->name, 'matched_on' => $field];
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|null>  $values
     * @param  array<string, array<string, string>>  $seen
     */
    private function remember(array $values, array &$seen): void
    {
        foreach (['email', 'kvk_number', 'name'] as $field) {
            $value = $values[$field] ?? null;

            if ($value !== null) {
                $seen[$field][Str::lower($value)] = $values['name'] ?? $value;
            }
        }
    }
}
