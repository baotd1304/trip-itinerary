<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `trips`
            MODIFY `status` ENUM('pending','editting','confirmed','rejected')
            NOT NULL DEFAULT 'pending'");

        Schema::table('trips', function (Blueprint $table) {
            $table->text('reject_reason')->nullable()->after('note');
            $table->timestamp('submitted_at')->nullable()->after('reject_reason');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reject_reason', 'submitted_at', 'reviewed_at']);
        });

        DB::statement("UPDATE `trips` SET `status` = 'pending' WHERE `status` = 'editting'");
        DB::statement("ALTER TABLE `trips`
            MODIFY `status` ENUM('pending','confirmed','rejected')
            NOT NULL DEFAULT 'pending'");
    }
};