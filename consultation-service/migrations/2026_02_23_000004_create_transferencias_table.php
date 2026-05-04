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
            $table->string('codigo_transferencia')->unique();
            
            // Relacionamentos
            $table->foreignId('consulta_id')->nullable()->constrained('consultas')->onDelete('cascade');
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            
            // Tipo de transferência
            $table->enum('tipo_transferencia', [
                'medico',
                'especialidade',
                'hospital',
                'unidade'
            ]);
            
            // Origem
            $table->foreignId('medico_origem_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('especialidade_origem')->nullable();
            $table->string('hospital_origem')->nullable();
            
            // Destino
            $table->foreignId('medico_destino_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('especialidade_destino')->nullable();
            $table->string('hospital_destino')->nullable();
            
            // Motivo e observações
            $table->text('motivo');
            $table->text('observacoes')->nullable();
            $table->text('condicao_paciente')->nullable();
            
            // Status e controle
            $table->enum('status', [
                'solicitada',
                'aceita',
                'recusada',
                'concluida',
                'cancelada'
            ])->default('solicitada');
            
            $table->enum('prioridade', ['normal', 'urgente', 'emergencia'])->default('normal');
            
            // Datas do processo
            $table->timestamp('data_solicitacao')->useCurrent();
            $table->timestamp('data_aceite')->nullable();
            $table->timestamp('data_recusa')->nullable();
            $table->timestamp('data_conclusao')->nullable();
            $table->timestamp('data_cancelamento')->nullable();
            
            // Aceite/Recusa
            $table->foreignId('aceita_por')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('recusada_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('motivo_recusa')->nullable();
            
            // Cancelamento
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('motivo_cancelamento')->nullable();
            
            // Documentação
            $table->json('documentos_anexos')->nullable();
            $table->text('sumario_clinico')->nullable();
            
            // Transporte (se necessário)
            $table->boolean('necessita_transporte')->default(false);
            $table->string('tipo_transporte')->nullable(); // Ambulância, SAMU, etc.
            $table->text('observacoes_transporte')->nullable();
            
            // Pagamento (se aplicável)
            $table->boolean('pagamento_necessario')->default(false);
            $table->decimal('valor_pagamento', 10, 2)->nullable();
            $table->enum('status_pagamento', ['pendente', 'processado', 'cancelado'])->nullable();
            $table->timestamp('data_pagamento')->nullable();
            
            // Auditoria
            $table->foreignId('criado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('agendamento_id');
            $table->index('paciente_id');
            $table->index('medico_origem_id');
            $table->index('medico_destino_id');
            $table->index('tipo_transferencia');
            $table->index('status');
            $table->index('prioridade');
            $table->index(['paciente_id', 'status']);
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
