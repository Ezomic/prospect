<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case New = 'new';
    case Sent = 'sent';
    case Replied = 'replied';
    case Bounced = 'bounced';
    case Closed = 'closed';

    /**
     * How far through the pipeline this status is. Used when merging: a Sent
     * company folded into a New one must not lose the fact that it was
     * contacted, so the further-along status survives.
     */
    public function rank(): int
    {
        return match ($this) {
            self::New => 0,
            self::Sent => 1,
            self::Bounced => 2,
            // A reply outranks a bounce: a company can bounce on one address
            // and answer from another, and the answer is the real outcome.
            self::Replied => 3,
            // Closed is a decision someone made, so nothing overrides it.
            self::Closed => 4,
        };
    }

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
