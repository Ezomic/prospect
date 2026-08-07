<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteStoredFiles();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cv_path', 'cv_original_name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cv_path')->nullable();
            $table->string('cv_original_name')->nullable();
        });
    }

    /**
     * The upload is on the local disk, not in the database, so dropping the
     * columns alone would strand the files with nothing left pointing at them.
     */
    private function deleteStoredFiles(): void
    {
        foreach (DB::table('users')->whereNotNull('cv_path')->pluck('cv_path') as $path) {
            if (is_string($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }
};
