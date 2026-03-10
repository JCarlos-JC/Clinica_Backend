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
            
            // Relacionamentos
            $table->unsignedBigInteger('consulta_id');
            $table->string('nid', 50)->nullable();
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->unsignedBigInteger('medico_solicitante_id')->nullable();
            $table->string('medico_solicitante_nome')->nullable();
            
            // Tipo de exame
            $table->string('tipo_exame'); // laboratorial, imagem, eletrocardiograma, etc
            $table->string('nome_exame', 255);
            $table->string('codigo_exame', 100)->nullable(); // código TUSS ou interno
            $table->text('descricao')->nullable();
            $table->text('indicacao_clinica')->nullable();
            
            // Solicitação
            $table->date('data_solicitacao');
            $table->enum('urgencia', ['normal', 'urgente', 'emergencia'])->default('normal');
            $table->text('observacoes_solicitacao')->nullable();
            $table->text('informacoes_clinicas')->nullable(); // histórico relevante
            
            // Agendamento
            $table->date('data_agendamento')->nullable();
            $table->time('hora_agendamento')->nullable();
            $table->string('local_realizacao')->nullable();
            $table->string('setor')->nullable(); // laboratório, radiologia, etc
            
            // Coleta/Realização
            $table->dateTime('data_hora_coleta')->nullable();
            $table->unsignedBigInteger('coletado_por')->nullable(); // user_id
            $table->string('material_biologico', 100)->nullable(); // sangue, urina, fezes
            $table->text('preparo_necessario')->nullable();
            $table->boolean('jejum_necessario')->default(false);
            $table->integer('horas_jejum')->nullable();
            
            // Análise
            $table->dateTime('data_hora_analise')->nullable();
            $table->unsignedBigInteger('analisado_por')->nullable(); // user_id do técnico
            $table->string('equipamento_utilizado')->nullable();
            $table->string('metodo_analise')->nullable();
            
            // Resultados
            $table->json('resultados')->nullable(); // {parametro: valor}
            $table->json('valores_referencia')->nullable(); // {parametro: "min-max"}
            $table->text('interpretacao')->nullable();
            $table->text('observacoes_resultado')->nullable();
            $table->enum('resultado_qualitativo', [
                'normal',
                'alterado',
                'critico',
                'inconclusivo'
            ])->nullable();
            
            // Laudo médico
            $table->dateTime('data_hora_laudo')->nullable();
            $table->unsignedBigInteger('laudado_por')->nullable(); // médico que fez o laudo
            $table->string('laudado_por_nome')->nullable();
            $table->string('laudado_por_crm', 50)->nullable();
            $table->text('laudo_medico')->nullable();
            $table->text('conclusao')->nullable();
            $table->text('recomendacoes')->nullable();
            
            // Anexos
            $table->json('arquivos_anexos')->nullable(); // [{"nome": "", "path": "", "tipo": ""}]
            $table->string('caminho_resultado_pdf')->nullable();
            $table->string('caminho_imagem')->nullable();
            
            // Notificações
            $table->boolean('notificar_medico')->default(true);
            $table->boolean('notificar_paciente')->default(false);
            $table->dateTime('data_notificacao_medico')->nullable();
            $table->dateTime('data_notificacao_paciente')->nullable();
            
            // Status
            $table->enum('status', [
                'solicitado',
                'agendado',
                'aguardando_coleta',
                'coletado',
                'em_analise',
                'laudado',
                'disponivel',
                'visualizado',
                'cancelado'
            ])->default('solicitado');
            
            // Custos
            $table->decimal('valor_exame', 10, 2)->nullable();
            $table->enum('status_pagamento', [
                'nao_cobrado',
                'pendente',
                'pago',
                'isento'
            ])->default('nao_cobrado');
            
            // Cancelamento
            $table->text('motivo_cancelamento')->nullable();
            $table->dateTime('data_cancelamento')->nullable();
            $table->unsignedBigInteger('cancelado_por')->nullable();
            
            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('nid');
            $table->index('paciente_id');
            $table->index('medico_solicitante_id');
            $table->index('tipo_exame');
            $table->index('status');
            $table->index('data_solicitacao');
            $table->index('data_agendamento');
            $table->index('urgencia');
            $table->index('resultado_qualitativo');
            
            // Foreign keys
            $table->foreign('consulta_id')->references('id')->on('consultas')->onDelete('cascade');
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
