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
        Schema::create('precos_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_consulta_id')->constrained('tipos_consulta')->onDelete('cascade');
            $table->foreignId('tipo_utente_id')->constrained('tipo_utentes')->onDelete('cascade');
            $table->decimal('valor', 10, 2); // Valor da consulta em MZN
            $table->string('descricao')->nullable(); // Descrição opcional
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Garantir que cada combinação seja única
            $table->unique(['tipo_consulta_id', 'tipo_utente_id'], 'unique_tipo_consulta_utente');
            
            // Índices para performance
            $table->index('tipo_consulta_id');
            $table->index('tipo_utente_id');
            $table->index('ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precos_consultas');
    }
};
