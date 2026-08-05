<?php

namespace App\Enums;

enum LetterStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Sending = 'sending';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ready => 'Ready',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
        };
    }
}
