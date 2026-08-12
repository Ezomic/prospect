<?php

namespace App\Enums;

enum InteractionKind: string
{
    case Call = 'call';
    case Meeting = 'meeting';
    case LinkedIn = 'linkedin';
    case Note = 'note';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Meeting => 'Meeting',
            self::LinkedIn => 'LinkedIn',
            self::Note => 'Note',
            self::Other => 'Other',
        };
    }
}
