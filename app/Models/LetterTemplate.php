<?php

namespace App\Models;

use App\Enums\LetterLanguage;
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
 * @property LetterLanguage $language
 * @property string $subject
 * @property string $body
 * @property string $email_subject
 * @property string $email_body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'language', 'subject', 'body', 'email_subject', 'email_body'])]
class LetterTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => LetterType::class, 'language' => LetterLanguage::class];
    }

    /**
     * The copy the app ships with, keyed by language and then letter type.
     * Also what a reset restores, so it stays here rather than only in the
     * migrations that seeded it.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public const DEFAULTS = [
        'nl' => [
            'open_aanbod' => [
                'subject' => 'Open aanbod: freelance softwareontwikkeling voor {{ company }}',
                'body' => "{{ greeting }},\n\nMijn naam is Robbin Thijssen, freelance softwareontwikkelaar bij Thijssen Software. {{ opening }} Graag stel ik mij via deze weg voor.\n\nIk bouw moderne webapplicaties met Laravel, Vue en TypeScript, van het eerste ontwerp tot oplevering, hosting en onderhoud. Mijn kracht zit in het zelfstandig oppakken van een vraagstuk en het opleveren van werkende, onderhoudbare software.\n\nIk denk dat ik van waarde kan zijn voor het ontwikkelteam van {{ company }}. In een kort gesprek licht ik graag toe wat ik voor u kan betekenen. Voorbeelden van mijn werk vindt u op thijssensoftware.nl.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
                'email_subject' => 'Open aanbod - Robbin Thijssen (Thijssen Software)',
                'email_body' => "{{ greeting }},\n\nBijgaand stuur ik u mijn open aanbod als freelance softwareontwikkelaar. In de brief licht ik toe wat ik voor {{ company }} kan betekenen, en op thijssensoftware.nl vindt u een overzicht van mijn werk.\n\nIk hoor graag of er mogelijkheden zijn om kennis te maken.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
            ],
            'follow_up' => [
                'subject' => 'Nog even terugkomend op mijn open aanbod',
                'body' => "{{ greeting }},\n\nOp {{ previous_sent_at }} stuurde ik u mijn open aanbod als freelance softwareontwikkelaar. Wellicht is het in de drukte ondergesneeuwd, vandaar dit korte bericht.\n\nMocht {{ company }} op enig moment behoefte hebben aan een ervaren ontwikkelaar voor een project of een tijdelijke versterking van het team, dan denk ik graag mee. Een kort gesprek is genoeg om te bepalen of het zinvol is.\n\nVoorbeelden van mijn werk vindt u op thijssensoftware.nl.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
                'email_subject' => 'Nog even terugkomend op mijn open aanbod',
                'email_body' => "{{ greeting }},\n\nOp {{ previous_sent_at }} stuurde ik u mijn open aanbod. Bijgaand nogmaals de brief, mocht die zijn ondergesneeuwd.\n\nIk hoor graag of er mogelijkheden zijn om kennis te maken.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
            ],
        ],
        // Deliberately not "Initiativbewerbung": that is the German word for a
        // speculative job application, and this is a supplier offering
        // services. Same reasoning as PROS-38 dropping "open sollicitatie".
        'de' => [
            'open_aanbod' => [
                'subject' => 'Freiberufliche Softwareentwicklung für {{ company }}',
                'body' => "{{ greeting }},\n\nmein Name ist Robbin Thijssen, freiberuflicher Softwareentwickler bei Thijssen Software. {{ opening }} Auf diesem Weg möchte ich mich gerne vorstellen.\n\nIch entwickle moderne Webanwendungen mit Laravel, Vue und TypeScript, vom ersten Entwurf bis zur Auslieferung, zum Hosting und zur Wartung. Meine Stärke liegt darin, eine Aufgabenstellung eigenständig zu übernehmen und funktionierende, wartbare Software zu liefern.\n\nIch denke, dass ich für das Entwicklungsteam von {{ company }} von Wert sein kann. In einem kurzen Gespräch erläutere ich Ihnen gerne, was ich für Sie tun kann. Beispiele meiner Arbeit finden Sie auf thijssensoftware.nl.\n\nMit freundlichen Grüßen,\n\nRobbin Thijssen\nThijssen Software",
                'email_subject' => 'Freiberufliche Softwareentwicklung - Robbin Thijssen (Thijssen Software)',
                'email_body' => "{{ greeting }},\n\nanbei sende ich Ihnen mein Angebot als freiberuflicher Softwareentwickler. Im Brief erläutere ich, was ich für {{ company }} tun kann, und auf thijssensoftware.nl finden Sie einen Überblick über meine Arbeit.\n\nIch höre gerne, ob es Möglichkeiten für ein Kennenlernen gibt.\n\nMit freundlichen Grüßen,\n\nRobbin Thijssen\nThijssen Software",
            ],
            'follow_up' => [
                'subject' => 'Kurze Nachfrage zu meinem Angebot',
                'body' => "{{ greeting }},\n\nam {{ previous_sent_at }} habe ich Ihnen mein Angebot als freiberuflicher Softwareentwickler geschickt. Vielleicht ist es im Alltag untergegangen, daher diese kurze Nachricht.\n\nSollte {{ company }} zu irgendeinem Zeitpunkt einen erfahrenen Entwickler für ein Projekt oder zur zeitweiligen Verstärkung des Teams benötigen, denke ich gerne mit. Ein kurzes Gespräch genügt, um zu klären, ob es sinnvoll ist.\n\nBeispiele meiner Arbeit finden Sie auf thijssensoftware.nl.\n\nMit freundlichen Grüßen,\n\nRobbin Thijssen\nThijssen Software",
                'email_subject' => 'Kurze Nachfrage zu meinem Angebot',
                'email_body' => "{{ greeting }},\n\nam {{ previous_sent_at }} habe ich Ihnen mein Angebot geschickt. Anbei noch einmal der Brief, falls er untergegangen ist.\n\nIch höre gerne, ob es Möglichkeiten für ein Kennenlernen gibt.\n\nMit freundlichen Grüßen,\n\nRobbin Thijssen\nThijssen Software",
            ],
        ],
        'en' => [
            'open_aanbod' => [
                'subject' => 'Freelance software development for {{ company }}',
                'body' => "{{ greeting }},\n\nMy name is Robbin Thijssen, a freelance software developer at Thijssen Software. {{ opening }} I would like to introduce myself.\n\nI build modern web applications with Laravel, Vue and TypeScript, from the first design through to delivery, hosting and maintenance. My strength is taking a problem on independently and delivering working, maintainable software.\n\nI think I could be of value to the development team at {{ company }}. In a short conversation I would be glad to explain what I can do for you. Examples of my work are at thijssensoftware.nl.\n\nKind regards,\n\nRobbin Thijssen\nThijssen Software",
                'email_subject' => 'Freelance software development - Robbin Thijssen (Thijssen Software)',
                'email_body' => "{{ greeting }},\n\nAttached is my offer as a freelance software developer. The letter explains what I can do for {{ company }}, and thijssensoftware.nl has an overview of my work.\n\nI would be glad to hear whether there is an opportunity to talk.\n\nKind regards,\n\nRobbin Thijssen\nThijssen Software",
            ],
            'follow_up' => [
                'subject' => 'Following up on my offer',
                'body' => "{{ greeting }},\n\nOn {{ previous_sent_at }} I sent you my offer as a freelance software developer. It may well have got lost in the day to day, hence this short note.\n\nIf {{ company }} ever needs an experienced developer for a project, or temporary extra capacity in the team, I would be glad to help think it through. A short conversation is enough to work out whether it makes sense.\n\nExamples of my work are at thijssensoftware.nl.\n\nKind regards,\n\nRobbin Thijssen\nThijssen Software",
                'email_subject' => 'Following up on my offer',
                'email_body' => "{{ greeting }},\n\nOn {{ previous_sent_at }} I sent you my offer. The letter is attached again in case it got lost.\n\nI would be glad to hear whether there is an opportunity to talk.\n\nKind regards,\n\nRobbin Thijssen\nThijssen Software",
            ],
        ],
    ];

    public static function current(
        LetterType $type = LetterType::OpenAanbod,
        LetterLanguage $language = LetterLanguage::Dutch,
    ): self {
        return self::query()->firstOrCreate(
            ['type' => $type, 'language' => $language],
            self::defaultsFor($type, $language),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function defaultsFor(LetterType $type, LetterLanguage $language): array
    {
        return self::DEFAULTS[$language->value][$type->value];
    }
}
