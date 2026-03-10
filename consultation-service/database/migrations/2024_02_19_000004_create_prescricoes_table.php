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
        Schema::create('prescricoes', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->unsignedBigInteger('consulta_id');
            $table->string('nid', 50)->nullable(); // NID do paciente
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->unsignedBigInteger('medico_id')->nullable();
            $table->string('medico_nome')->nullable();
            $table->string('medico_crm', 50)->nullable();
            
            // Dados do medicamento
            $table->string('medicamento', 255);
            $table->string('principio_ativo', 255)->nullable();
            $table->string('dosagem', 100);
            $table->string('forma_farmaceutica', 100)->nullable(); // comprimido, xarope, injetável
            $table->string('via_administracao', 100)->nullable(); // oral, intravenosa, etc
            
            // Posologia
            $table->string('frequencia', 100); // 8/8h, 12/12h, 1x ao dia
            $table->integer('quantidade_por_dose')->nullable();
            $table->string('unidade_medida', 50)->nullable(); // mg, ml, comprimido
            $table->json('horarios_list')->nullable(); // ["08:00", "14:00", "20:00"]
            $table->integer('duracao_dias')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            
            // Observações e recomendações
            $table->text('observacoes')->nullable();
            $table->text('orientacoes_uso')->nullable();
            $table->text('efeitos_colaterais')->nullable();
            $table->text('contraindicacoes')->nullable();
            $table->boolean('uso_continuo')->default(false);
            $table->boolean('se_necessario')->default(false); // uso sob demanda
            
            // Controle de dispensação
            $table->integer('quantidade_total')->nullable();
            $table->integer('quantidade_dispensada')->default(0);
            $table->date('data_dispensacao')->nullable();
            $table->string('local_dispensacao')->nullable();
            $table->unsignedBigInteger('dispensado_por')->nullable(); // user_id do farmacêutico
            
            // Informações complementares
            $table->boolean('medicamento_controlado')->default(false);
            $table->string('numero_receita', 100)->nullable();
            $table->date('validade_receita')->nullable();
            $table->boolean('receituario_especial')->default(false);
            $table->string('cor_receituario', 50)->nullable(); // amarelo, azul, branco
            
            // Status
            $table->enum('status', [
                'prescrita',
                'dispensada',
                'parcialmente_dispensada',
                'cancelada',
                'substituida',
                'concluida'
            ])->default('prescrita');
            
            // Substituição
            $table->unsignedBigInteger('prescricao_substituida_id')->nullable();
            $table->text('motivo_substituicao')->nullable();
            
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
            $table->index('status');
            $table->index('data_inicio');
            $table->index('data_fim');
            $table->index('medicamento_controlado');
            
            // Foreign keys
            $table->foreign('consulta_id')->references('id')->on('consultas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescricoes');
    }
};
