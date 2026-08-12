<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('from');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('message_id')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            // Unique per company rather than globally: one bounce can name two
            // tracked recipients, and that is one message stored against each
            // of them, not a collision. Nulls repeat freely in SQLite, so a
            // message without an id simply does not deduplicate.
            $table->unique(['company_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_messages');
    }
};
