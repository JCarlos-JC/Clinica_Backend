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
        Schema::create('precos_exames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_exame_id')->constrained('tipos_exame')->onDelete('cascade');
            $table->foreignId('tipo_utente_id')->constrained('tipo_utentes')->onDelete('cascade');
            $table->decimal('valor', 10, 2);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('tipo_exame_id');
            $table->index('tipo_utente_id');
            $table->index('ativo');
            
            // Chave única para evitar duplicatas
            $table->unique(['tipo_exame_id', 'tipo_utente_id'], 'unique_tipo_exame_utente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precos_exames');
    }
};
