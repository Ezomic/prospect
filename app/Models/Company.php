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
 * @property Carbon|null $replied_at
 * @property Carbon|null $bounced_at
 * @property Carbon|null $follow_up_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Letter> $letters
 */
#[Fillable(['name', 'website', 'email', 'contact_name', 'city', 'kvk_number', 'industry', 'status', 'notes'])]
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
