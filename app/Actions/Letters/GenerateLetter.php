<?php

namespace App\Actions\Letters;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Models\Company;
use App\Models\Letter;
use App\Models\LetterTemplate;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

class GenerateLetter
{
    public function handle(Company $company, LetterType $type = LetterType::OpenAanbod): Letter
    {
        $template = LetterTemplate::current($type);
        $values = self::placeholders($company);

        return $company->letters()->create([
            'type' => $type,
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
            'previous_sent_at' => self::previousSentAt($company),
            'contact' => $company->contact_name ?? '',
            'city' => $company->city ?? '',
            'industry' => $company->industry ?? '',
            'greeting' => self::greeting($company),
            'opening' => self::opening($company),
        ];
    }

    /**
     * The date the last letter actually went out, which the follow-up copy
     * refers back to. Blank when nothing has been sent, so a follow-up written
     * before any send reads oddly rather than claiming a date that never was.
     */
    private static function previousSentAt(Company $company): string
    {
        $sentAt = $company->letters()
            ->whereNotNull('sent_at')
            ->max('sent_at');

        if (! is_string($sentAt) && ! $sentAt instanceof DateTimeInterface) {
            return '';
        }

        // locale() is documented as returning either the instance or the
        // current locale string, so the result is narrowed rather than assumed.
        $dutch = Date::parse($sentAt)->locale('nl');

        return $dutch instanceof CarbonInterface ? $dutch->isoFormat('D MMMM YYYY') : '';
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
