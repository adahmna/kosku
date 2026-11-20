<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['owner', 'admin', 'pengelola'])
            ->update(['role' => 'pemilik']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('pemilik')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('pengelola')->change();
        });
    }
};
