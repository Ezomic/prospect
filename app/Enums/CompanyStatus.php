<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case New = 'new';
    case Sent = 'sent';
    case Replied = 'replied';
    case Bounced = 'bounced';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Sent => 'Sent',
            self::Replied => 'Replied',
            self::Bounced => 'Bounced',
            self::Closed => 'Closed',
        };
    }
}
