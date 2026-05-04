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
        Schema::create('altas', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->unsignedBigInteger('consulta_id');
            $table->string('nid', 50)->nullable();
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->string('paciente_nome')->nullable();
            $table->unsignedBigInteger('medico_id')->nullable();
            $table->string('medico_nome')->nullable();
            $table->string('medico_crm', 50)->nullable();
            
            // Tipo de alta
            $table->enum('tipo_alta', [
                'alta_melhorada',
                'alta_curada',
                'alta_criterio_clinico',
                'alta_pedido_paciente',
                'alta_evasao',
                'transferencia',
                'obito'
            ]);
            
            // Data e hora
            $table->dateTime('data_hora_alta');
            $table->integer('dias_internacao')->nullable();
            
            // Diagnóstico final
            $table->text('diagnostico_entrada')->nullable();
            $table->text('diagnostico_final');
            $table->string('cid_principal', 20)->nullable();
            $table->json('cids_secundarios')->nullable();
            
            // Resumo da internação
            $table->text('resumo_atendimento')->nullable();
            $table->text('evolucao_clinica')->nullable();
            $table->text('procedimentos_realizados')->nullable();
            $table->json('exames_realizados')->nullable();
            $table->text('intercorrencias')->nullable();
            
            // Condições de alta
            $table->enum('condicao_alta', [
                'bom_estado_geral',
                'regular',
                'necessita_cuidados',
                'critico'
            ])->nullable();
            $table->json('sinais_vitais_alta')->nullable();
            $table->boolean('deambulando')->default(true);
            $table->boolean('orientado')->default(true);
            $table->boolean('alimentando_se')->default(true);
            
            // Recomendações
            $table->text('recomendacoes_gerais')->nullable();
            $table->text('cuidados_domiciliares')->nullable();
            $table->text('orientacoes_dieta')->nullable();
            $table->text('orientacoes_atividades')->nullable();
            $table->text('sinais_alerta')->nullable(); // quando retornar ao hospital
            
            // Medicamentos de alta
            $table->json('medicamentos_alta')->nullable(); // [{medicamento, dose, frequencia, duracao}]
            $table->boolean('receita_entregue')->default(false);
            $table->boolean('atestado_entregue')->default(false);
            $table->integer('dias_atestado')->nullable();
            
            // Retorno
            $table->boolean('necessita_retorno')->default(true);
            $table->date('data_retorno')->nullable();
            $table->string('especialidade_retorno')->nullable();
            $table->string('local_retorno')->nullable();
            $table->text('motivo_retorno')->nullable();
            
            // Exames de controle
            $table->json('exames_controle')->nullable(); // exames a realizar em casa
            $table->text('orientacoes_exames')->nullable();
            
            // Encaminhamentos
            $table->boolean('encaminhado_especialista')->default(false);
            $table->string('especialista_encaminhado')->nullable();
            $table->text('motivo_encaminhamento')->nullable();
            
            // Acompanhante/Responsável
            $table->string('acompanhante_nome')->nullable();
            $table->string('acompanhante_parentesco')->nullable();
            $table->string('acompanhante_cpf', 14)->nullable();
            $table->string('acompanhante_telefone', 20)->nullable();
            $table->boolean('acompanhante_orientado')->default(false);
            
            // Documentos entregues
            $table->json('documentos_entregues')->nullable(); // [receitas, atestados, laudos, etc]
            $table->boolean('prontuario_atualizado')->default(false);
            
            // Alta administrativa
            $table->boolean('pendencias_financeiras')->default(false);
            $table->text('descricao_pendencias')->nullable();
            $table->boolean('liberado_administrativo')->default(false);
            $table->dateTime('data_liberacao_administrativa')->nullable();
            
            // Satisfação
            $table->integer('avaliacao_atendimento')->nullable(); // 1-5 estrelas
            $table->text('observacoes_paciente')->nullable();
            
            // Observações gerais
            $table->text('observacoes')->nullable();
            
            // Status
            $table->enum('status', [
                'pendente',
                'concluida',
                'cancelada'
            ])->default('concluida');
            
            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('nid');
            $table->index('paciente_id');
            $table->index('medico_id');
            $table->index('tipo_alta');
            $table->index('data_hora_alta');
            $table->index('data_retorno');
            $table->index('status');
            
            // Foreign keys
            $table->foreign('consulta_id')->references('id')->on('consultas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('altas');
    }
};
