<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Cria nova tabela common_pragues
        Schema::create('common_pragues', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 2️⃣ Adiciona coluna common_prague_id à tabela prague_common_names
        Schema::table('prague_common_names', function (Blueprint $table) {
            $table->unsignedBigInteger('common_prague_id')->nullable()->after('prague_id');
        });

        // 4️⃣ Remove coluna antiga 'name'
        Schema::table('prague_common_names', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // 5️⃣ Cria FK
        Schema::table('prague_common_names', function (Blueprint $table) {
            $table->foreign('common_prague_id')
                  ->references('id')->on('common_pragues')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Remove FK e coluna
        Schema::table('prague_common_names', function (Blueprint $table) {
            $table->dropForeign(['common_prague_id']);
            $table->dropColumn('common_prague_id');
            $table->string('name')->nullable();
        });

        Schema::dropIfExists('common_pragues');
    }
};
