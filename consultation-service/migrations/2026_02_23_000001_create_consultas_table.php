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
            $table->string('codigo_consulta')->unique();
            
            // Relacionamentos
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->onDelete('set null');
            $table->foreignId('triagem_id')->nullable()->constrained('triagens')->onDelete('set null');
            
            // Dados da consulta
            $table->string('especialidade')->nullable();
            $table->string('tipo_consulta')->nullable(); // primeira_vez, retorno, urgencia, etc.
            $table->text('historico')->nullable();
            $table->text('queixa_principal')->nullable();
            $table->text('sintomas')->nullable();
            $table->text('exame_fisico')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('recomendacoes')->nullable();
            $table->text('observacoes')->nullable();
            
            // Sinais vitais na consulta
            $table->decimal('pressao_arterial_sistolica', 5, 2)->nullable();
            $table->decimal('pressao_arterial_diastolica', 5, 2)->nullable();
            $table->decimal('frequencia_cardiaca', 5, 2)->nullable();
            $table->decimal('temperatura', 4, 2)->nullable();
            $table->decimal('saturacao_oxigenio', 5, 2)->nullable();
            $table->decimal('peso', 6, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            
            // Datas
            $table->timestamp('data_hora_inicio')->nullable();
            $table->timestamp('data_hora_fim')->nullable();
            $table->integer('duracao_minutos')->nullable();
            
            // Status e controle
            $table->enum('status', [
                'agendado',
                'em_atendimento',
                'aguardando_exames',
                'aguardando_prescricao',
                'transferido_medico',
                'transferido_especialidade',
                'finalizada',
                'alta',
                'obito',
                'cancelada'
            ])->default('agendado');
            
            $table->enum('prioridade', ['normal', 'urgente', 'emergencia'])->default('normal');
            
            // Indicadores
            $table->boolean('exames_solicitados')->default(false);
            $table->boolean('prescricoes_adicionadas')->default(false);
            $table->boolean('retorno_marcado')->default(false);
            $table->date('data_retorno')->nullable();
            
            // Auditoria
            $table->foreignId('criado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('finalizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_finalizacao')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('paciente_id');
            $table->index('medico_id');
            $table->index('agendamento_id');
            $table->index('status');
            $table->index('data_hora_inicio');
            $table->index(['paciente_id', 'status']);
            $table->index(['medico_id', 'status']);
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
