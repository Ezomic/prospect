<?php

namespace App\Enums;

enum LetterStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ready => 'Ready',
            self::Sent => 'Sent',
        };
    }
}
