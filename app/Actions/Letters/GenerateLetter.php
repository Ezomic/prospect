<?php

namespace App\Actions\Letters;

use App\Enums\LetterLanguage;
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
        $template = LetterTemplate::current($type, $company->language);
        $values = self::placeholders($company);

        return $company->letters()->create([
            'type' => $type,
            'language' => $company->language,
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

        // Written in the letter's own language: a German follow-up citing a
        // Dutch month name reads as a mail merge that went wrong.
        // locale() is documented as returning either the instance or the
        // current locale string, so the result is narrowed rather than assumed.
        $localised = Date::parse($sentAt)->locale($company->language->locale());

        return $localised instanceof CarbonInterface
            ? $localised->isoFormat($company->language->dateFormat())
            : '';
    }

    /**
     * German has no clean equivalent of "Beste <voornaam>": "Sehr geehrter
     * Herr" needs a gender the CRM does not hold, so the neutral "Guten Tag"
     * is used with a name and the formal salutation without one.
     */
    private static function greeting(Company $company): string
    {
        $contact = $company->contact_name;

        return match ($company->language) {
            LetterLanguage::German => $contact !== null
                ? "Guten Tag {$contact}"
                : 'Sehr geehrte Damen und Herren',
            LetterLanguage::English => $contact !== null
                ? "Dear {$contact}"
                : 'Dear Sir or Madam',
            LetterLanguage::Dutch => $contact !== null
                ? "Beste {$contact}"
                : 'Geachte heer, mevrouw',
        };
    }

    private static function opening(Company $company): string
    {
        $name = $company->name;
        $city = $company->city;
        $industry = $company->industry;

        return match ($company->language) {
            LetterLanguage::German => ($city !== null
                ? "{$name} in {$city} ist mir aufgefallen"
                : "{$name} ist mir aufgefallen")
                .($industry !== null ? " im Bereich {$industry}." : '.'),
            LetterLanguage::English => ($city !== null
                ? "{$name} in {$city} caught my eye"
                : "{$name} caught my eye")
                .($industry !== null ? " within {$industry}." : '.'),
            LetterLanguage::Dutch => ($city !== null
                ? "{$name} in {$city} viel mij op"
                : "{$name} viel mij op")
                .($industry !== null ? " binnen de {$industry}." : '.'),
        };
    }
}
