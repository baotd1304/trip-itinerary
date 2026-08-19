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
            // $table->integer('quantity')->default(0);         // số lượng từ form
            // $table->decimal('unit_price', 15, 2)->default(0); // đơn giá từ bảng expenses
            // $table->decimal('subtotal', 15, 2)->default(0);   // = quantity * unit_price hoặc giá trị nhập trực tiếp

            $table->decimal('overtime', 15,0)->unsigned()->nullable();
            $table->decimal('overtime_rate', 15,0)->unsigned()->nullable();
            $table->decimal('overnight', 15,0)->unsigned()->nullable();
            $table->decimal('overnight_rate', 15,0)->unsigned()->nullable();
            $table->decimal('toll_fee', 15,0)->unsigned()->nullable();
            $table->decimal('airport_fee', 15,0)->unsigned()->nullable();
            $table->decimal('holiday_rate', 15,0)->unsigned()->nullable();
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
