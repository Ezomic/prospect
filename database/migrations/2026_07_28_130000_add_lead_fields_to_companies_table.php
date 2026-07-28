<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('source')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->unsignedTinyInteger('lead_score')->nullable();
            $table->string('first_contact_channel')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['source', 'contact_role', 'linkedin_url', 'lead_score', 'first_contact_channel']);
        });
    }
};
