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
        Schema::create('return_details', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel returns
            $table->foreignId('return_id')
                  ->constrained('returns')
                  ->restrictOnDelete();

            // Relasi ke tabel products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->restrictOnDelete();

            // Jumlah barang yang diretur
            $table->decimal('quantity');

            // Harga jual saat transaksi
            $table->decimal('selling_price', 15, 2);

            // Total nilai retur per item
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_details');
    }
};