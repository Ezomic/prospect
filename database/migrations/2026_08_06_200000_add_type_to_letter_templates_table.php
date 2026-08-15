<?php

use App\Enums\LetterType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inlined rather than read from LetterTemplate: a migration is a snapshot
     * of a moment, and the model's constants have since been restructured by
     * language. Reaching into the model would break this on a fresh install.
     *
     * @var array<string, string>
     */
    private const FOLLOW_UP_COPY = [
        'subject' => 'Nog even terugkomend op mijn open aanbod',
        'body' => "{{ greeting }},\n\nOp {{ previous_sent_at }} stuurde ik u mijn open aanbod als freelance softwareontwikkelaar. Wellicht is het in de drukte ondergesneeuwd, vandaar dit korte bericht.\n\nMocht {{ company }} op enig moment behoefte hebben aan een ervaren ontwikkelaar voor een project of een tijdelijke versterking van het team, dan denk ik graag mee. Een kort gesprek is genoeg om te bepalen of het zinvol is.\n\nVoorbeelden van mijn werk vindt u op thijssensoftware.nl.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
        'email_subject' => 'Nog even terugkomend op mijn open aanbod',
        'email_body' => "{{ greeting }},\n\nOp {{ previous_sent_at }} stuurde ik u mijn open aanbod. Bijgaand nogmaals de brief, mocht die zijn ondergesneeuwd.\n\nIk hoor graag of er mogelijkheden zijn om kennis te maken.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
    ];

    public function up(): void
    {
        Schema::table('letter_templates', function (Blueprint $table) {
            $table->string('type')->default(LetterType::OpenAanbod->value);
        });

        // The existing row is the open aanbod, whatever the user has edited it
        // into. Seed the follow-up alongside it rather than leaving the type
        // with no template to find.
        DB::table('letter_templates')->update(['type' => LetterType::OpenAanbod->value]);

        DB::table('letter_templates')->insert([
            ...self::FOLLOW_UP_COPY,
            'type' => LetterType::FollowUp->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('letter_templates', function (Blueprint $table) {
            $table->unique('type');
        });
    }

    public function down(): void
    {
        DB::table('letter_templates')->where('type', LetterType::FollowUp->value)->delete();

        Schema::table('letter_templates', function (Blueprint $table) {
            $table->dropUnique(['type']);
            $table->dropColumn('type');
        });
    }
};
