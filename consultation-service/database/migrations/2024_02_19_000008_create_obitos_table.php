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
        Schema::create('obitos', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->unsignedBigInteger('consulta_id');
            $table->string('nid', 50)->nullable();
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->string('paciente_nome')->nullable();
            $table->date('paciente_data_nascimento')->nullable();
            $table->string('paciente_cpf', 14)->nullable();
            
            // Médico declarante
            $table->unsignedBigInteger('medico_declarante_id')->nullable();
            $table->string('medico_declarante_nome')->nullable();
            $table->string('medico_declarante_crm', 50)->nullable();
            
            // Data e hora
            $table->dateTime('data_hora_obito');
            $table->string('local_obito')->nullable(); // emergência, UTI, enfermaria, etc
            $table->enum('tipo_obito', [
                'natural',
                'acidental',
                'violento',
                'suspeito',
                'a_esclarecer'
            ]);
            
            // Causa do óbito
            $table->text('causa_imediata'); // causa que levou diretamente ao óbito
            $table->text('causa_intermediaria')->nullable();
            $table->text('causa_basica')->nullable(); // doença ou lesão que iniciou
            $table->text('outras_causas')->nullable();
            $table->string('cid_principal', 20)->nullable();
            $table->json('cids_relacionados')->nullable();
            
            // Circunstâncias
            $table->text('circunstancias_obito')->nullable();
            $table->text('historico_doenca')->nullable();
            $table->integer('tempo_evolucao_dias')->nullable();
            $table->text('tratamento_realizado')->nullable();
            $table->json('exames_realizados')->nullable();
            
            // Informações complementares
            $table->enum('tipo_morte', [
                'esperada',
                'inesperada',
                'subita'
            ])->nullable();
            $table->boolean('morte_materna')->default(false);
            $table->boolean('morte_relacionada_gravidez')->default(false);
            $table->boolean('morte_relacionada_parto')->default(false);
            $table->boolean('morte_violenta')->default(false);
            $table->boolean('acidente_trabalho')->default(false);
            
            // Declaração de Óbito
            $table->string('numero_declaracao_obito', 100)->nullable();
            $table->date('data_emissao_declaracao')->nullable();
            $table->string('cartorio')->nullable();
            $table->string('numero_registro_cartorio', 100)->nullable();
            
            // Necropsia
            $table->enum('necropsia', [
                'nao_realizada',
                'solicitada',
                'em_andamento',
                'concluida'
            ])->default('nao_realizada');
            $table->text('motivo_necropsia')->nullable();
            $table->dateTime('data_hora_necropsia')->nullable();
            $table->unsignedBigInteger('medico_legista_id')->nullable();
            $table->string('medico_legista_nome')->nullable();
            $table->text('laudo_necropsia')->nullable();
            $table->json('achados_necropsia')->nullable();
            
            // Notificações
            $table->boolean('familia_notificada')->default(false);
            $table->dateTime('data_hora_notificacao_familia')->nullable();
            $table->string('notificado_quem')->nullable(); // nome do familiar
            $table->string('notificado_parentesco')->nullable();
            $table->boolean('autoridade_policial_notificada')->default(false);
            $table->dateTime('data_hora_notificacao_policia')->nullable();
            $table->string('numero_boletim_ocorrencia', 100)->nullable();
            
            // Corpo
            $table->enum('destino_corpo', [
                'aguardando_familia',
                'liberado_familia',
                'encaminhado_iml',
                'encaminhado_necropsia',
                'sepultado'
            ])->nullable();
            $table->dateTime('data_hora_liberacao_corpo')->nullable();
            $table->string('liberado_para')->nullable(); // nome da funerária ou familiar
            $table->string('documento_liberado_para', 50)->nullable(); // CPF/CNPJ
            $table->string('funeraria')->nullable();
            $table->string('numero_protocolo_funeraria', 100)->nullable();
            
            // Sepultamento
            $table->date('data_sepultamento')->nullable();
            $table->string('cemiterio')->nullable();
            $table->string('numero_jazigo', 100)->nullable();
            
            // Pertences do paciente
            $table->json('pertences_entregues')->nullable();
            $table->string('pertences_recebidos_por')->nullable();
            $table->dateTime('data_entrega_pertences')->nullable();
            
            // Documentação e prontuário
            $table->boolean('prontuario_finalizado')->default(false);
            $table->boolean('contas_encerradas')->default(false);
            $table->text('pendencias_administrativas')->nullable();
            
            // Observações
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable(); // não vão para família
            
            // Auditoria e controle
            $table->boolean('caso_notificavel')->default(false); // vigilância epidemiológica
            $table->string('tipo_notificacao')->nullable();
            $table->boolean('notificacao_enviada')->default(false);
            
            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('nid');
            $table->index('paciente_id');
            $table->index('medico_declarante_id');
            $table->index('data_hora_obito');
            $table->index('tipo_obito');
            $table->index('numero_declaracao_obito');
            $table->index('morte_violenta');
            $table->index('caso_notificavel');
            
            // Foreign keys
            $table->foreign('consulta_id')->references('id')->on('consultas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obitos');
    }
};
