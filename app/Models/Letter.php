<?php

namespace App\Models;

use App\Enums\LetterStatus;
use Database\Factories\LetterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $subject
 * @property string $body
 * @property string|null $email_subject
 * @property string|null $email_body
 * @property LetterStatus $status
 * @property string|null $send_error
 * @property Carbon|null $generated_at
 * @property Carbon|null $queued_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 */
#[Fillable(['subject', 'body', 'email_subject', 'email_body', 'status', 'generated_at', 'queued_at', 'sent_at', 'send_error'])]
class Letter extends Model
{
    /** @use HasFactory<LetterFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LetterStatus::class,
            'generated_at' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
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
