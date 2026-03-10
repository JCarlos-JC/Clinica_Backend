<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_utentes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('codigo', 50)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->boolean('isento_pagamento')->default(false);
            $table->decimal('percentual_desconto', 5, 2)->default(0.00);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_utentes');
    }
};