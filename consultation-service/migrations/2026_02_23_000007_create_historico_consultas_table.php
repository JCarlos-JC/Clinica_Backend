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
        Schema::create('historico_consultas', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Tipo de ação
            $table->enum('tipo_acao', [
                'criacao',
                'atualizacao',
                'inicio_atendimento',
                'finalizacao',
                'cancelamento',
                'transferencia',
                'alta',
                'obito',
                'adicao_prescricao',
                'remocao_prescricao',
                'adicao_exame',
                'cancelamento_exame',
                'alteracao_status'
            ]);
            
            // Descrição da ação
            $table->text('descricao')->nullable();
            
            // Dados anteriores e novos (para auditoria)
            $table->json('dados_anteriores')->nullable();
            $table->json('dados_novos')->nullable();
            
            // Status
            $table->string('status_anterior')->nullable();
            $table->string('status_novo')->nullable();
            
            // IP e User Agent
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('data_acao')->useCurrent();
            
            // Índices
            $table->index('consulta_id');
            $table->index('paciente_id');
            $table->index('usuario_id');
            $table->index('tipo_acao');
            $table->index('data_acao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_consultas');
    }
};
