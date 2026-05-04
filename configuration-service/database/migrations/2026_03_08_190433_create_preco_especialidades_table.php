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
        Schema::create('preco_especialidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('especialidade_id');
            $table->unsignedBigInteger('tipo_utente_id');
            $table->decimal('valor', 10, 2);
            $table->enum('estado', ['Ativo', 'Inativo'])->default('Ativo');
            $table->text('descricao')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('especialidade_id');
            $table->index('tipo_utente_id');
            $table->index('estado');
            
            // Unique constraint para evitar duplicatas
            $table->unique(['especialidade_id', 'tipo_utente_id']);
            
            // Foreign keys
            $table->foreign('especialidade_id')->references('id')->on('especialidades')->onDelete('cascade');
            $table->foreign('tipo_utente_id')->references('id')->on('tipo_utentes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preco_especialidades');
    }
};
