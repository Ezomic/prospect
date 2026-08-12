<?php

namespace App\Models;

use App\Enums\InteractionKind;
use Database\Factories\InteractionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Something that happened with a company and was logged by hand: a call, a
 * meeting, a LinkedIn message. The company's notes field describes what the
 * company is; interactions record what has happened, in order.
 *
 * @property int $id
 * @property int $company_id
 * @property InteractionKind $kind
 * @property Carbon $occurred_at
 * @property string $summary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 */
#[Fillable(['kind', 'occurred_at', 'summary'])]
class Interaction extends Model
{
    /** @use HasFactory<InteractionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => InteractionKind::class,
            'occurred_at' => 'datetime',
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
