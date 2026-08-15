<?php

use App\Enums\LetterLanguage;
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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('language')->default(LetterLanguage::Dutch->value);
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->string('language')->default(LetterLanguage::Dutch->value);
        });

        Schema::table('letter_templates', function (Blueprint $table) {
            // The unique key moves from type alone to (type, language): the
            // same letter type now exists once per language.
            $table->dropUnique(['type']);
            $table->string('language')->default(LetterLanguage::Dutch->value);
        });

        DB::table('letter_templates')->update(['language' => LetterLanguage::Dutch->value]);

        foreach ([LetterLanguage::German, LetterLanguage::English] as $language) {
            foreach (LetterType::cases() as $type) {
                DB::table('letter_templates')->insert([
                    ...LetterTemplate::defaultsFor($type, $language),
                    'type' => $type->value,
                    'language' => $language->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('letter_templates', function (Blueprint $table) {
            $table->unique(['type', 'language']);
        });
    }

    public function down(): void
    {
        DB::table('letter_templates')
            ->whereIn('language', [LetterLanguage::German->value, LetterLanguage::English->value])
            ->delete();

        Schema::table('letter_templates', function (Blueprint $table) {
            $table->dropUnique(['type', 'language']);
            $table->dropColumn('language');
            $table->unique('type');
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn('language');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
