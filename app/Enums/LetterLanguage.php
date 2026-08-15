<?php

namespace App\Enums;

enum LetterLanguage: string
{
    case Dutch = 'nl';
    case German = 'de';
    case English = 'en';

    public function label(): string
    {
        return match ($this) {
            self::Dutch => 'Nederlands',
            self::German => 'Deutsch',
            self::English => 'English',
        };
    }

    /**
     * The locale dates are written in. A German follow-up citing a Dutch month
     * name reads as a mail merge that went wrong.
     */
    public function locale(): string
    {
        return $this->value;
    }

    /**
     * German writes the day as an ordinal: "27. Juli 2026", not "27 Juli 2026".
     * Getting this wrong is small but it is exactly the sort of detail that
     * marks a letter as machine-generated.
     */
    public function dateFormat(): string
    {
        return match ($this) {
            self::German => 'D. MMMM YYYY',
            default => 'D MMMM YYYY',
        };
    }
}
