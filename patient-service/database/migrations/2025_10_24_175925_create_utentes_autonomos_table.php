<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utentes_autonomos', function (Blueprint $table) {
            $table->id();
            $table->string('nid')->nullable()->unique();

            // Informações Pessoais
            $table->string('nome');
            $table->string('apelido');
            $table->date('data_nascimento')->nullable();
            $table->enum('genero', ['Masculino', 'Feminino', 'Outro'])->default('Masculino');
            $table->unsignedBigInteger('tipo_documento_id')->nullable(); // Referência externa
            $table->string('bilhete_identidade', 50)->nullable();
            
            // Informações de Contato
            $table->string('celular', 20);
            $table->string('celular_alternativo', 20)->nullable();
            $table->string('email')->nullable();
            
            // Hospital de Proveniência
            $table->string('hospital_proveniencia')->nullable();
            
            // Solicitações de Exames
            $table->json('exames_solicitados')->nullable(); // Array de exames solicitados
            $table->dateTime('data_solicitacao')->nullable();
            $table->enum('status', [
                'pendente', 
                'aceito', 
                'pago', 
                'pago_laboratorio', 
                'processando', 
                'concluido'
            ])->default('pendente');
            
            // Pagamento
            $table->unsignedBigInteger('tipos_exame_id')->nullable(); // Referência externa
            // $table->decimal('valor_exames', 10, 2)->nullable();
            $table->unsignedBigInteger('metodo_pagamento_id')->nullable(); // Referência externa
            $table->dateTime('data_pagamento')->nullable();
            
            // Resultados de Exames
            $table->json('resultados_exames')->nullable();
            $table->dateTime('data_resultados')->nullable();
            $table->dateTime('data_exames')->nullable();
            
            // Histórico de Exames
            $table->json('historico_exames')->nullable();
            
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('nome');
            $table->index('apelido');
            $table->index('status');
            $table->index('data_solicitacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utentes_autonomos');
    }
};