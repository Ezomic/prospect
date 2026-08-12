<?php

namespace App\Enums;

enum InboundMessageKind: string
{
    case Reply = 'reply';
    case Bounce = 'bounce';

    public function label(): string
    {
        return match ($this) {
            self::Reply => 'Reply',
            self::Bounce => 'Bounce',
        };
    }
}
