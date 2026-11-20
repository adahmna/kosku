<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('nama');
            $table->string('nik', 32);
            $table->string('hp', 32);
            $table->string('email');
            $table->text('alamat');
            $table->date('tanggal_masuk');
            $table->unsignedInteger('durasi_bulan')->default(12);
            $table->date('tanggal_berakhir');
            $table->decimal('deposit', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->string('status')->default('Aktif');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
