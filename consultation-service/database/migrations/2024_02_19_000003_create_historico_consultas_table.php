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
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            
            // Ação realizada
            $table->string('acao')->comment('status_alterado, medico_transferido, consulta_iniciada, etc');
            $table->enum('tipo_acao', [
                'criacao',
                'atualizacao',
                'exclusao',
                'status_alterado',
                'transferencia',
                'prescricao',
                'exame',
                'alta',
                'obito',
                'outro'
            ])->default('atualizacao');
            
            // Estados
            $table->string('status_anterior')->nullable();
            $table->string('status_novo')->nullable();
            $table->json('dados_anteriores')->nullable(); // estado completo antes da mudança
            $table->json('dados_novos')->nullable(); // estado completo depois da mudança
            $table->text('detalhes')->nullable();
            
            // Usuário que realizou a ação
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nome')->nullable();
            $table->string('usuario_papel')->nullable(); // médico, enfermeiro, admin, etc
            
            // Dados de auditoria
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('dispositivo')->nullable(); // mobile, desktop, tablet
            $table->string('navegador')->nullable();
            $table->string('sistema_operacional')->nullable();
            
            // Localização
            $table->string('localizacao_geografica')->nullable();
            $table->string('terminal')->nullable(); // identificação do computador/terminal
            
            // Observações
            $table->text('observacoes')->nullable();
            $table->boolean('acao_critica')->default(false); // marcar ações sensíveis
            
            $table->timestamps();
            
            // Índices
            $table->index(['consulta_id', 'created_at']);
            $table->index('usuario_id');
            $table->index('tipo_acao');
            $table->index('acao_critica');
            $table->index('created_at');
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
