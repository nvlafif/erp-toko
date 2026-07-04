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
        Schema::create('returns', function (Blueprint $table) {
            $table->id();

            // Transaksi yang diretur
            $table->foreignId('transaction_id')
                  ->constrained('transactions')
                  ->restrictOnDelete();

            // User yang memproses retur
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Waktu retur
            $table->dateTime('return_date');

            // Total nominal retur
            $table->decimal('return_total', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};