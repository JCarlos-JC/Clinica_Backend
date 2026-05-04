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
        Schema::create('exames', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_exame')->unique();
            
            // Relacionamentos
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_solicitante_id')->constrained('users')->onDelete('cascade');
            
            // Dados do exame
            $table->string('tipo_exame'); // Hemograma, Glicemia, etc.
            $table->string('categoria_exame')->nullable(); // Laboratorial, Imagem, etc.
            $table->text('descricao')->nullable();
            $table->text('indicacao_clinica')->nullable();
            
            // Prioridade e status
            $table->enum('prioridade', ['normal', 'urgente', 'emergencia'])->default('normal');
            $table->enum('status', [
                'solicitado',
                'agendado',
                'coletado',
                'em_analise',
                'concluido',
                'cancelado'
            ])->default('solicitado');
            
            // Datas do processo
            $table->timestamp('data_solicitacao')->useCurrent();
            $table->timestamp('data_agendamento')->nullable();
            $table->timestamp('data_coleta')->nullable();
            $table->timestamp('data_conclusao')->nullable();
            $table->timestamp('data_cancelamento')->nullable();
            
            // Coleta
            $table->foreignId('coletado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observacoes_coleta')->nullable();
            
            // Resultados
            $table->json('resultados')->nullable(); // Estrutura: {"parametro": "valor", ...}
            $table->text('laudo')->nullable();
            $table->text('conclusao')->nullable();
            $table->json('valores_referencia')->nullable();
            
            // Anexos
            $table->json('anexos')->nullable(); // Array de URLs de arquivos
            
            // Profissional responsável pelo laudo
            $table->foreignId('laudado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_laudo')->nullable();
            
            // Local de realização
            $table->string('laboratorio')->nullable();
            $table->string('local_coleta')->nullable();
            
            // Cancelamento
            $table->string('motivo_cancelamento')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->onDelete('set null');
            
            // Notificação
            $table->boolean('medico_notificado')->default(false);
            $table->timestamp('data_notificacao_medico')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('paciente_id');
            $table->index('medico_solicitante_id');
            $table->index('status');
            $table->index('prioridade');
            $table->index(['paciente_id', 'status']);
            $table->index('tipo_exame');
            $table->index('data_solicitacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exames');
    }
};
