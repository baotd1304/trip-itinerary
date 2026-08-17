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
        Schema::create('trip_expense', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade'); 
            $table->integer('overtime')->unsigned()->nullable();
            $table->integer('overnight')->unsigned()->nullable();
            $table->integer('toll_fee')->unsigned()->nullable();
            $table->integer('airport_fee')->unsigned()->nullable();
            $table->integer('holiday')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_expense');
    }
};
