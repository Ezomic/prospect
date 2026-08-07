<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('do_not_contact_at')->nullable();
            $table->text('do_not_contact_reason')->nullable();
        });

        // Anything already flagged was set before a reason could be recorded.
        // Stamping it now keeps "flagged" and "when" from disagreeing.
        DB::table('companies')
            ->where('do_not_contact', true)
            ->update(['do_not_contact_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['do_not_contact_at', 'do_not_contact_reason']);
        });
    }
};
