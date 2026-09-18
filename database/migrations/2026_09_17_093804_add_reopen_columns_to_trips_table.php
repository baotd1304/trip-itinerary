<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('reopen_reason')->nullable()->after('note');
            $table->foreignId('reopened_by')->nullable()->after('reopen_reason')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reopened_by');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['reopen_reason', 'reopened_at']);
        });
    }
};