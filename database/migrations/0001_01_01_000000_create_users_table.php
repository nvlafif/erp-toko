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
        Schema::create('users', function (Blueprint $table) {
            $table->id();                              // Primary Key
            $table->string('name');                   // Nama akun (Owner, Admin Gudang, Kasir 1)
            $table->string('username')->unique();     // Username untuk login
            $table->string('password');               // Password (Hash)
            $table->string('role');                   // Owner | Admin Gudang | Kasir
            $table->timestamps();                     // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};