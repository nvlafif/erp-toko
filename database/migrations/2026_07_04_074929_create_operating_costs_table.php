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
        Schema::create('operating_costs', function (Blueprint $table) {
            $table->id();

            // Nama biaya operasional
            $table->string('expense_name');

            // Nominal biaya
            $table->decimal('amount', 15, 2);

            // Waktu biaya dikeluarkan
            $table->dateTime('expense_date');

            // created_at & updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operating_costs');
    }
};