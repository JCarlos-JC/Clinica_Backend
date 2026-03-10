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
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            
            // Dados do Agendamento (vindos da triagem)
            $table->unsignedBigInteger('agendamento_id')->nullable()->comment('ID do agendamento da triagem');
            $table->string('nid')->index()->comment('NID do paciente');
            $table->unsignedBigInteger('paciente_id')->nullable()->index();
            $table->unsignedBigInteger('triagem_id')->nullable()->index();
            
            // Dados do Médico
            $table->string('medico')->nullable();
            $table->unsignedBigInteger('medico_id')->nullable()->index();
            
            // Tipo de Consulta e Especialidade
            $table->string('tipo_consulta')->nullable();
            $table->unsignedBigInteger('tipo_consulta_id')->nullable();
            $table->string('especialidade')->nullable();
            $table->unsignedBigInteger('especialidade_id')->nullable();
            
            // Data e Hora
            $table->date('data_consulta')->index();
            $table->time('hora_consulta');
            $table->dateTime('data_hora_inicio')->nullable();
            $table->dateTime('data_hora_fim')->nullable();
            
            // Motivo e Observações
            $table->text('motivo_consulta');
            $table->text('observacoes')->nullable();
            
            // Anamnese e Exame Físico
            $table->text('anamnese')->nullable()->comment('História clínica do paciente');
            $table->text('exame_fisico')->nullable()->comment('Exame físico realizado');
            $table->text('hipotese_diagnostica')->nullable()->comment('Hipótese diagnóstica');
            
            // Prescrição e Tratamento
            $table->text('prescricao')->nullable()->comment('Prescrição médica');
            $table->text('procedimentos')->nullable()->comment('Procedimentos realizados');
            $table->text('exames_solicitados')->nullable()->comment('Exames laboratoriais solicitados');
            
            // Plano de Tratamento
            $table->text('plano_tratamento')->nullable()->comment('Plano terapêutico');
            $table->text('orientacoes')->nullable()->comment('Orientações ao paciente');
            $table->date('data_retorno')->nullable()->comment('Data de retorno agendada');
            
            // Status e Controle
            $table->enum('status', [
                'agendada',
                'em_atendimento',
                'finalizada',
                'cancelada',
                'nao_compareceu',
                'remarcada'
            ])->default('agendada')->index();
            
            $table->enum('prioridade', ['normal', 'urgente', 'emergencia'])->default('normal');
            
            // Dados de Pagamento
            $table->decimal('valor_consulta', 10, 2)->nullable();
            $table->enum('status_pagamento', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->string('forma_pagamento')->nullable();
            $table->dateTime('data_pagamento')->nullable();
            
            // Transferência e Encaminhamento
            $table->boolean('transferido')->default(false);
            $table->string('medico_anterior')->nullable();
            $table->text('motivo_transferencia')->nullable();
            $table->text('encaminhamento')->nullable()->comment('Encaminhamento para especialista');
            
            // Atestado e Documentos
            $table->text('atestado_medico')->nullable();
            $table->integer('dias_atestado')->nullable();
            $table->text('documentos_anexos')->nullable()->comment('JSON com anexos');
            
            // Controle de Sincronização
            $table->boolean('sincronizado_triagem')->default(false);
            $table->dateTime('data_sincronizacao_triagem')->nullable();
            
            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices compostos para otimização
            $table->index(['data_consulta', 'status']);
            $table->index(['medico_id', 'data_consulta']);
            $table->index(['paciente_id', 'data_consulta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
