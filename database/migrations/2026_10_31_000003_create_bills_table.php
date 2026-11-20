<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('sewa', 12, 2)->default(0);
            $table->decimal('listrik', 12, 2)->default(0);
            $table->decimal('air', 12, 2)->default(0);
            $table->decimal('tambahan', 12, 2)->default(0);
            $table->string('ket_tambahan')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->date('jatuh_tempo');
            $table->string('status')->default('Belum Lunas');
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode')->nullable();
            $table->string('bukti_bayar')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
