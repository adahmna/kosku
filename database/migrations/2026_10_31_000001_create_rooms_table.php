<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->string('tipe');
            $table->unsignedInteger('lantai');
            $table->decimal('harga', 12, 2);
            $table->unsignedInteger('luas');
            $table->json('fasilitas');
            $table->string('status')->default('Tersedia');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
