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
            $table->string('codigo_prescricao')->unique();
            
            // Relacionamentos
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_id')->constrained('users')->onDelete('cascade');
            
            // Dados do medicamento
            $table->string('medicamento');
            $table->string('quantidade');
            $table->string('unidade'); // comprimido, cápsula, ml, mg, etc.
            $table->string('via_administracao')->default('Oral'); // Oral, Injetável, Tópica, etc.
            
            // Dosagem e posologia
            $table->string('dose_diaria'); // número de doses por dia
            $table->string('numero_dias'); // duração do tratamento
            $table->text('horarios')->nullable(); // horários formatados (ex: 08:00, 14:00, 20:00)
            $table->json('horarios_list')->nullable(); // array de horários
            $table->text('dosagem')->nullable(); // texto descritivo da dosagem
            
            // Observações
            $table->text('comentario')->nullable();
            $table->text('observacoes')->nullable();
            
            // Controle de dispensação
            $table->enum('status', [
                'prescrita',
                'dispensada',
                'parcialmente_dispensada',
                'cancelada'
            ])->default('prescrita');
            
            $table->timestamp('data_dispensacao')->nullable();
            $table->foreignId('dispensado_por')->nullable()->constrained('users')->onDelete('set null');
            
            // Controle de uso
            $table->boolean('uso_continuo')->default(false);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            
            // Auditoria
            $table->foreignId('criado_por')->constrained('users')->onDelete('cascade');
            $table->string('criado_por_nome')->nullable();
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('paciente_id');
            $table->index('medico_id');
            $table->index('status');
            $table->index(['paciente_id', 'status']);
            $table->index('medicamento');
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
