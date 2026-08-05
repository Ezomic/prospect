<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The open-aanbod letter and cover email as editable text. A single row: this
 * is one freelancer's outreach, not a multi-tenant app.
 *
 * @property int $id
 * @property string $subject
 * @property string $body
 * @property string $email_subject
 * @property string $email_body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['subject', 'body', 'email_subject', 'email_body'])]
class LetterTemplate extends Model
{
    /**
     * The copy the app shipped with. Also what a reset restores, so it stays
     * here rather than only in the migration that seeded it.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'subject' => 'Open sollicitatie: freelance softwareontwikkeling voor {{ company }}',
        'body' => <<<'LETTER'
        {{ greeting }},

        Mijn naam is Robbin Thijssen, freelance softwareontwikkelaar bij Thijssen Software. {{ opening }} Graag stel ik mij via deze weg voor.

        Ik bouw moderne webapplicaties met Laravel, Vue en TypeScript, van het eerste ontwerp tot oplevering, hosting en onderhoud. Mijn kracht zit in het zelfstandig oppakken van een vraagstuk en het opleveren van werkende, onderhoudbare software.

        Ik denk dat ik van waarde kan zijn voor het ontwikkelteam van {{ company }}. In een kort gesprek licht ik graag toe wat ik voor u kan betekenen. Mijn cv vindt u in de bijlage.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        LETTER,
        'email_subject' => 'Open sollicitatie - Robbin Thijssen (Thijssen Software)',
        'email_body' => <<<'EMAIL'
        {{ greeting }},

        Bijgaand stuur ik u mijn open sollicitatie als freelance softwareontwikkelaar, samen met mijn cv. In de brief licht ik toe wat ik voor {{ company }} kan betekenen.

        Ik hoor graag of er mogelijkheden zijn om kennis te maken.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        EMAIL,
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], self::DEFAULTS);
    }
}
