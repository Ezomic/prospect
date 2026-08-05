<?php

use App\Models\LetterTemplate;
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
        DB::table('letter_templates')->insert([
            ...LetterTemplate::DEFAULTS,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
    }
};
