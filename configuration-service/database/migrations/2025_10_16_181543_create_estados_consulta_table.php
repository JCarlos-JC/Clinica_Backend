<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_consulta', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->string('cor', 20)->nullable()->comment('Código de cor para interface');
            $table->string('icone', 50)->nullable()->comment('Nome do ícone para interface');
            $table->boolean('estado_final')->default(false)->comment('Se true, finaliza o ciclo da consulta');
            $table->boolean('requer_encerramento_ciclo')->default(false);
            $table->integer('ordem_exibicao')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_consulta');
    }
};