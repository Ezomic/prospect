<?php

use App\Enums\LetterType;
use App\Models\LetterTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            ...LetterTemplate::FOLLOW_UP_DEFAULTS,
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
