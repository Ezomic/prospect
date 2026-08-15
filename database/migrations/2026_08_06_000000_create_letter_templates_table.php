<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->string('email_subject');
            $table->text('email_body');
            $table->timestamps();
        });

        // Seed the copy that used to be hardcoded, so generating a letter
        // behaves identically the moment this deploys.
        //
        // Inlined rather than read from LetterTemplate: a migration is a
        // snapshot of a moment, and the model's constants have since been
        // restructured by type and language. Reaching into the model breaks
        // this on a fresh install, which is exactly what happened.
        DB::table('letter_templates')->insert([
            'subject' => 'Open sollicitatie: freelance softwareontwikkeling voor {{ company }}',
            'body' => "{{ greeting }},\n\nMijn naam is Robbin Thijssen, freelance softwareontwikkelaar bij Thijssen Software. {{ opening }} Graag stel ik mij via deze weg voor.\n\nIk bouw moderne webapplicaties met Laravel, Vue en TypeScript, van het eerste ontwerp tot oplevering, hosting en onderhoud. Mijn kracht zit in het zelfstandig oppakken van een vraagstuk en het opleveren van werkende, onderhoudbare software.\n\nIk denk dat ik van waarde kan zijn voor het ontwikkelteam van {{ company }}. In een kort gesprek licht ik graag toe wat ik voor u kan betekenen. Mijn cv vindt u in de bijlage.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
            'email_subject' => 'Open sollicitatie - Robbin Thijssen (Thijssen Software)',
            'email_body' => "{{ greeting }},\n\nBijgaand stuur ik u mijn open sollicitatie als freelance softwareontwikkelaar, samen met mijn cv. In de brief licht ik toe wat ik voor {{ company }} kan betekenen.\n\nIk hoor graag of er mogelijkheden zijn om kennis te maken.\n\nMet vriendelijke groet,\n\nRobbin Thijssen\nThijssen Software",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
    }
};
