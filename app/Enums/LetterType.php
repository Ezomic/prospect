<?php

namespace App\Enums;

enum LetterType: string
{
    case OpenAanbod = 'open_aanbod';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::OpenAanbod => 'Open aanbod',
            self::FollowUp => 'Follow-up',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OpenAanbod => 'The first letter to a company that has not heard from you.',
            self::FollowUp => 'A shorter letter for a company already written to, referring back to the first.',
        };
    }
}
