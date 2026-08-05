<?php

namespace App\Actions\Letters;

use App\Enums\LetterStatus;
use App\Models\Company;
use App\Models\Letter;
use App\Models\LetterTemplate;

class GenerateLetter
{
    public function handle(Company $company): Letter
    {
        $template = LetterTemplate::current();
        $values = self::placeholders($company);

        return $company->letters()->create([
            'subject' => self::render($template->subject, $values),
            'body' => self::render($template->body, $values),
            'email_subject' => self::render($template->email_subject, $values),
            'email_body' => self::render($template->email_body, $values),
            'status' => LetterStatus::Draft,
            'generated_at' => now(),
        ]);
    }

    /**
     * An unknown placeholder is left as written rather than blanked, so a typo
     * is visible in the draft instead of quietly eating a sentence.
     *
     * @param  array<string, string>  $values
     */
    public static function render(string $template, array $values): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn (array $match) => $values[$match[1]] ?? $match[0],
            $template,
        );
    }

    /**
     * @return array<string, string>
     */
    public static function placeholders(Company $company): array
    {
        return [
            'company' => $company->name,
            'contact' => $company->contact_name ?? '',
            'city' => $company->city ?? '',
            'industry' => $company->industry ?? '',
            'greeting' => self::greeting($company),
            'opening' => self::opening($company),
        ];
    }

    private static function greeting(Company $company): string
    {
        return $company->contact_name !== null
            ? "Beste {$company->contact_name}"
            : 'Geachte heer, mevrouw';
    }

    private static function opening(Company $company): string
    {
        $opening = $company->city !== null
            ? "{$company->name} in {$company->city} viel mij op"
            : "{$company->name} viel mij op";

        return $opening.($company->industry !== null
            ? " binnen de {$company->industry}."
            : '.');
    }
}
