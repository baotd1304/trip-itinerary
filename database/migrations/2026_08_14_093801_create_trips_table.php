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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('advisor');
            $table->string('driver');
            $table->foreignId('car_id')->constrained('cars');            
            $table->date('day');
            $table->string('origin');
            $table->string('destination');
            $table->timestamp('departure_time');
            $table->timestamp('arrival_time');
            $table->integer('odo_start')->unsigned();
            $table->integer('odo_end')->unsigned();
            $table->integer('distance')->unsigned();
            $table->boolean('is_confirm')->default(0);  // 0 = not confirmed, 1 = confirmed
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
