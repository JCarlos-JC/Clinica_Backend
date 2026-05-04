<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_exames', function (Blueprint $table) {
            $table->id();
            $table->string('paciente_nid', 20)->nullable(); // NID do paciente (ex: 0001/2025)
            $table->string('utente_autonomo_nid', 20)->nullable(); // NID do utente (ex: UT001/2025)
            $table->unsignedBigInteger('solicitante_id')->nullable(); // Médico que solicitou
            
            // Exames solicitados
            $table->json('exames_solicitados'); // Array de exames
            $table->json('exames_realizaveis')->nullable(); // Exames que podem ser realizados na clínica
            $table->json('exames_nao_realizaveis')->nullable(); // Exames que não podem ser realizados
            
            $table->dateTime('data_solicitacao');
            $table->enum('status', [
                'pendente', 
                'aceito', 
                'pago', 
                'em_laboratorio',
                'concluido',
                'cancelado'
            ])->default('pendente');
            
            // Pagamento
            $table->unsignedBigInteger('tipos_exame_id')->nullable();
            // $table->decimal('valor_total', 10, 2)->nullable();
            $table->unsignedBigInteger('metodo_pagamento_id')->nullable();
            $table->dateTime('data_pagamento')->nullable();
            
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('paciente_nid');
            $table->index('utente_autonomo_nid');
            $table->index('status');
            $table->index('data_solicitacao');
            
            // Foreign keys para garantir integridade referencial
            $table->foreign('paciente_nid')
                  ->references('nid')
                  ->on('pacientes')
                  ->onDelete('cascade');
                  
            $table->foreign('utente_autonomo_nid')
                  ->references('nid')
                  ->on('utentes_autonomos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_exames');
    }
};