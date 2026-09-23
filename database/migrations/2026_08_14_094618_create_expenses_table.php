<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            // $table->string('type');
            // $table->decimal('unit_price');
            // $table->boolean('is_active')->default(1);
            $table->decimal('overkm_rate', 15, 0)->unsigned()->nullable();
            $table->decimal('overtime_rate', 15, 0)->unsigned()->nullable();
            $table->decimal('overnight_rate', 15, 0)->unsigned()->nullable();
            $table->decimal('holiday_rate', 15, 0)->unsigned()->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
