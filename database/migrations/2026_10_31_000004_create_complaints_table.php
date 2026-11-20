<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('kategori');
            $table->string('prioritas')->default('Sedang');
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('status')->default('Baru');
            $table->date('tanggal_lapor');
            $table->date('tanggal_selesai')->nullable();
            $table->date('target_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
