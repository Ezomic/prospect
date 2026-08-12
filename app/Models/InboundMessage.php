<?php

namespace App\Models;

use App\Enums\InboundMessageKind;
use Database\Factories\InboundMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A message that arrived about a company and changed its place in the
 * pipeline. Kept so the reply can be read where the decision about what to do
 * next is made, rather than only in the mailbox.
 *
 * @property int $id
 * @property int $company_id
 * @property InboundMessageKind $kind
 * @property string $from
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $message_id
 * @property Carbon $received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 */
#[Fillable(['kind', 'from', 'subject', 'body', 'message_id', 'received_at'])]
class InboundMessage extends Model
{
    /** @use HasFactory<InboundMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => InboundMessageKind::class,
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
