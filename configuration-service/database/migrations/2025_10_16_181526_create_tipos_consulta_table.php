<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_consulta', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->boolean('requer_triagem')->default(true);
            $table->boolean('isento_pagamento')->default(false);
            $table->decimal('preco_padrao', 10, 2)->default(0.00);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_consulta');
    }
};