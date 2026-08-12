<?php

namespace App\Models;

use App\Enums\LetterType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The letter and cover email as editable text, one row per letter type: this
 * is one freelancer's outreach, not a multi-tenant app.
 *
 * @property int $id
 * @property LetterType $type
 * @property string $subject
 * @property string $body
 * @property string $email_subject
 * @property string $email_body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'subject', 'body', 'email_subject', 'email_body'])]
class LetterTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => LetterType::class];
    }

    /**
     * The follow-up copy. Deliberately short: it is written to someone who
     * already has the first letter, so re-introducing Robbin reads as a mail
     * merge rather than a follow-up.
     *
     * @var array<string, string>
     */
    public const FOLLOW_UP_DEFAULTS = [
        'subject' => 'Nog even terugkomend op mijn open aanbod',
        'body' => <<<'LETTER'
        {{ greeting }},

        Op {{ previous_sent_at }} stuurde ik u mijn open aanbod als freelance softwareontwikkelaar. Wellicht is het in de drukte ondergesneeuwd, vandaar dit korte bericht.

        Mocht {{ company }} op enig moment behoefte hebben aan een ervaren ontwikkelaar voor een project of een tijdelijke versterking van het team, dan denk ik graag mee. Een kort gesprek is genoeg om te bepalen of het zinvol is.

        Voorbeelden van mijn werk vindt u op thijssensoftware.nl.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        LETTER,
        'email_subject' => 'Nog even terugkomend op mijn open aanbod',
        'email_body' => <<<'EMAIL'
        {{ greeting }},

        Op {{ previous_sent_at }} stuurde ik u mijn open aanbod. Bijgaand nogmaals de brief, mocht die zijn ondergesneeuwd.

        Ik hoor graag of er mogelijkheden zijn om kennis te maken.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        EMAIL,
    ];

    /**
     * The copy the app shipped with. Also what a reset restores, so it stays
     * here rather than only in the migration that seeded it.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'subject' => 'Open aanbod: freelance softwareontwikkeling voor {{ company }}',
        'body' => <<<'LETTER'
        {{ greeting }},

        Mijn naam is Robbin Thijssen, freelance softwareontwikkelaar bij Thijssen Software. {{ opening }} Graag stel ik mij via deze weg voor.

        Ik bouw moderne webapplicaties met Laravel, Vue en TypeScript, van het eerste ontwerp tot oplevering, hosting en onderhoud. Mijn kracht zit in het zelfstandig oppakken van een vraagstuk en het opleveren van werkende, onderhoudbare software.

        Ik denk dat ik van waarde kan zijn voor het ontwikkelteam van {{ company }}. In een kort gesprek licht ik graag toe wat ik voor u kan betekenen. Voorbeelden van mijn werk vindt u op thijssensoftware.nl.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        LETTER,
        'email_subject' => 'Open aanbod - Robbin Thijssen (Thijssen Software)',
        'email_body' => <<<'EMAIL'
        {{ greeting }},

        Bijgaand stuur ik u mijn open aanbod als freelance softwareontwikkelaar. In de brief licht ik toe wat ik voor {{ company }} kan betekenen, en op thijssensoftware.nl vindt u een overzicht van mijn werk.

        Ik hoor graag of er mogelijkheden zijn om kennis te maken.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        EMAIL,
    ];

    public static function current(LetterType $type = LetterType::OpenAanbod): self
    {
        return self::query()->firstOrCreate(
            ['type' => $type],
            self::defaultsFor($type),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function defaultsFor(LetterType $type): array
    {
        return $type === LetterType::FollowUp ? self::FOLLOW_UP_DEFAULTS : self::DEFAULTS;
    }
}
