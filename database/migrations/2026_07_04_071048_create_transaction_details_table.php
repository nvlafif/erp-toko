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
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();

            // Relasi ke transaksi
            $table->foreignId('transaction_id')
                  ->constrained('transactions')
                  ->cascadeOnDelete();

            // Relasi ke barang
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->restrictOnDelete();

            // Jumlah barang yang dibeli
            $table->decimal('quantity');

            // Harga jual saat transaksi
            $table->decimal('selling_price', 15, 2);

            // Total harga item (quantity × selling_price)
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};