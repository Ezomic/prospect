<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->date('follow_up_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['replied_at', 'bounced_at', 'follow_up_at']);
        });
    }
};
