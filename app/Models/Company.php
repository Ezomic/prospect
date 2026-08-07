<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $website
 * @property string|null $email
 * @property string|null $contact_name
 * @property string|null $city
 * @property string|null $kvk_number
 * @property string|null $industry
 * @property CompanyStatus $status
 * @property string|null $notes
 * @property string|null $source
 * @property string|null $contact_role
 * @property string|null $linkedin_url
 * @property int|null $lead_score
 * @property string|null $first_contact_channel
 * @property bool $do_not_contact
 * @property Carbon|null $do_not_contact_at
 * @property string|null $do_not_contact_reason
 * @property Carbon|null $replied_at
 * @property Carbon|null $bounced_at
 * @property Carbon|null $follow_up_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Letter> $letters
 */
#[Fillable(['name', 'website', 'email', 'contact_name', 'city', 'kvk_number', 'industry', 'status', 'notes', 'source', 'contact_role', 'linkedin_url', 'lead_score', 'first_contact_channel'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'lead_score' => 'integer',
            'do_not_contact' => 'boolean',
            'do_not_contact_at' => 'datetime',
            'replied_at' => 'datetime',
            'bounced_at' => 'datetime',
            'follow_up_at' => 'date',
        ];
    }

    /**
     * @return HasMany<Letter, $this>
     */
    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class)->latest();
    }
}
