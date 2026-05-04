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
            $table->string('codigo_alta')->unique();
            
            // Relacionamentos
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_id')->constrained('users')->onDelete('cascade');
            
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
            
            // Condição na alta
            $table->text('condicao_saida')->nullable();
            $table->text('diagnostico_final')->nullable();
            $table->text('procedimentos_realizados')->nullable();
            
            // Recomendações e prescrições
            $table->text('recomendacoes_alta')->nullable();
            $table->json('medicamentos_alta')->nullable(); // Medicamentos para continuar em casa
            $table->text('cuidados_domiciliares')->nullable();
            $table->text('restricoes')->nullable();
            
            // Retorno
            $table->boolean('retorno_agendado')->default(false);
            $table->date('data_retorno')->nullable();
            $table->text('motivo_retorno')->nullable();
            
            // Sinais de alerta
            $table->text('sinais_alerta')->nullable(); // Quando retornar urgentemente
            
            // Data e horário
            $table->timestamp('data_hora_alta')->useCurrent();
            
            // Documentos
            $table->json('documentos_entregues')->nullable(); // Receitas, atestados, etc.
            $table->string('numero_atestado')->nullable();
            $table->integer('dias_atestado')->nullable();
            
            // Observações
            $table->text('observacoes')->nullable();
            
            // Acompanhante
            $table->string('nome_acompanhante')->nullable();
            $table->string('parentesco_acompanhante')->nullable();
            $table->string('telefone_acompanhante')->nullable();
            
            // Auditoria
            $table->foreignId('criado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('paciente_id');
            $table->index('medico_id');
            $table->index('tipo_alta');
            $table->index('data_hora_alta');
            $table->index(['paciente_id', 'data_hora_alta']);
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
