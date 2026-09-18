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
            $table->foreignId('car_id')->constrained('cars');            
            $table->date('day');
            $table->string('origin');
            $table->string('destination');
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->integer('odo_start')->unsigned();
            $table->integer('odo_end')->unsigned();
            $table->integer('distance')->unsigned();
            $table->string('status')->default('pending');  // pending, confirmed, rejected
            $table->decimal('total_fee',15,0)->nullable();
            $table->string('note')->nullable();
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
