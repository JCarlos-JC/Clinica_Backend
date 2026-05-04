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
        Schema::create('solicitacoes_triagem', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('estados_urgencia_id')->nullable();

            // Dados principais
            $table->dateTime('data_triagem');

            $table->enum('urgencia', ['emergencia', 'urgente', 'normal'])
                ->default('normal');

            $table->enum('status', ['aguardando_triagem', 'em_triagem', 'triagem_concluida', 'cancelada'])
                ->default('aguardando_triagem');

            // Controle de fluxo
            $table->boolean('ja_consultado')->default(false);
            $table->boolean('retorno_consulta')->default(false);

            // Avaliação clínica
            $table->enum('classificacao_risco', [
                'vermelho',
                'laranja',
                'amarelo',
                'verde',
                'azul'
            ])->nullable();

            $table->integer('prioridade_atendimento')->nullable();

            $table->json('resultados_exames')->nullable();

            // Datas do processo
            $table->dateTime('data_solicitacao')->nullable();
            $table->dateTime('data_inicio_triagem')->nullable();
            $table->dateTime('data_conclusao_triagem')->nullable();
            $table->dateTime('data_cancelamento')->nullable();

            // Observações
            $table->text('observacoes')->nullable();
            $table->text('motivo_cancelamento')->nullable();

            // Integração com triage-service
            $table->unsignedBigInteger('triagem_id')
                ->nullable()
                ->comment('ID da triagem no triage-service');

            $table->timestamps();

            // Índices
            $table->index('paciente_id');
            $table->index('estados_urgencia_id');
            $table->index('urgencia');
            $table->index('status');
            $table->index('data_solicitacao');
            $table->index('triagem_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_triagem');
    }
};
