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
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->unsignedBigInteger('consulta_id');
            $table->string('nid', 50)->nullable();
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->string('paciente_nome')->nullable();
            
            // Tipo de transferência
            $table->enum('tipo_transferencia', [
                'entre_medicos',
                'entre_especialidades',
                'entre_setores',
                'entre_hospitais',
                'para_uti',
                'para_enfermaria',
                'para_emergencia'
            ]);
            
            // Origem
            $table->unsignedBigInteger('medico_origem_id')->nullable();
            $table->string('medico_origem_nome')->nullable();
            $table->string('especialidade_origem')->nullable();
            $table->string('setor_origem')->nullable();
            $table->string('hospital_origem')->nullable();
            
            // Destino
            $table->unsignedBigInteger('medico_destino_id')->nullable();
            $table->string('medico_destino_nome')->nullable();
            $table->string('especialidade_destino')->nullable();
            $table->string('setor_destino')->nullable();
            $table->string('hospital_destino')->nullable();
            $table->string('endereco_destino')->nullable();
            $table->string('contato_destino')->nullable();
            
            // Motivo e sumário clínico
            $table->text('motivo_transferencia');
            $table->text('sumario_clinico')->nullable();
            $table->text('diagnostico_principal')->nullable();
            $table->text('diagnosticos_secundarios')->nullable();
            $table->text('procedimentos_realizados')->nullable();
            $table->json('medicamentos_em_uso')->nullable();
            $table->json('exames_realizados')->nullable();
            
            // Estado do paciente
            $table->enum('estado_geral', [
                'estavel',
                'regular',
                'grave',
                'critico'
            ])->nullable();
            $table->json('sinais_vitais')->nullable();
            $table->text('restricoes_alimentares')->nullable();
            $table->text('alergias')->nullable();
            $table->boolean('necessita_isolamento')->default(false);
            $table->string('tipo_isolamento')->nullable();
            
            // Urgência
            $table->enum('urgencia', ['normal', 'urgente', 'emergencia'])->default('normal');
            $table->dateTime('data_hora_solicitacao');
            $table->dateTime('data_hora_prevista')->nullable();
            $table->dateTime('data_hora_efetivada')->nullable();
            
            // Transporte
            $table->enum('tipo_transporte', [
                'ambulancia',
                'helicoptero',
                'maca',
                'cadeira_rodas',
                'deambulando',
                'outro'
            ])->nullable();
            $table->text('observacoes_transporte')->nullable();
            $table->boolean('necessita_oxigenio')->default(false);
            $table->boolean('necessita_monitor')->default(false);
            $table->boolean('necessita_bomba_infusao')->default(false);
            
            // Acompanhamento
            $table->string('acompanhante_nome')->nullable();
            $table->string('acompanhante_parentesco')->nullable();
            $table->string('acompanhante_contato')->nullable();
            
            // Documentos
            $table->json('documentos_transferencia')->nullable(); // prontuário, exames, etc
            $table->string('numero_protocolo', 100)->nullable();
            
            // Status e controle
            $table->enum('status', [
                'solicitada',
                'aguardando_vaga',
                'aguardando_transporte',
                'em_transito',
                'aceita',
                'recusada',
                'concluida',
                'cancelada'
            ])->default('solicitada');
            
            $table->text('motivo_recusa')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->dateTime('data_aceite')->nullable();
            $table->unsignedBigInteger('aceita_por')->nullable();
            $table->dateTime('data_recusa')->nullable();
            $table->unsignedBigInteger('recusada_por')->nullable();
            
            // Custos
            $table->decimal('valor_transporte', 10, 2)->nullable();
            $table->enum('responsavel_pagamento', [
                'paciente',
                'convenio',
                'sus',
                'hospital_origem',
                'hospital_destino'
            ])->nullable();
            
            // Observações gerais
            $table->text('observacoes')->nullable();
            $table->text('intercorrencias')->nullable();
            
            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('nid');
            $table->index('paciente_id');
            $table->index('tipo_transferencia');
            $table->index('medico_origem_id');
            $table->index('medico_destino_id');
            $table->index('status');
            $table->index('urgencia');
            $table->index('data_hora_solicitacao');
            
            // Foreign keys
            $table->foreign('consulta_id')->references('id')->on('consultas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
