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
            $table->string('codigo_obito')->unique();
            
            // Relacionamentos
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_id')->constrained('users')->onDelete('cascade');
            
            // Data e hora do óbito
            $table->timestamp('data_hora_obito');
            
            // Causa do óbito
            $table->text('causa_morte'); // Causa principal
            $table->text('causa_secundaria')->nullable();
            $table->text('causa_base')->nullable();
            
            // Tipo de óbito
            $table->enum('tipo_obito', [
                'natural',
                'acidente',
                'violencia',
                'indeterminado'
            ])->default('natural');
            
            // Local do óbito
            $table->string('local_obito')->nullable(); // Consultório, Enfermaria, Emergência, etc.
            
            // Circunstâncias
            $table->text('circunstancias')->nullable();
            $table->text('historico_clinico')->nullable();
            
            // Necropsia
            $table->boolean('necropsia_realizada')->default(false);
            $table->text('resultado_necropsia')->nullable();
            $table->timestamp('data_necropsia')->nullable();
            
            // Declaração de óbito
            $table->string('numero_declaracao_obito')->nullable();
            $table->timestamp('data_emissao_declaracao')->nullable();
            
            // Notificação
            $table->boolean('familia_notificada')->default(false);
            $table->timestamp('data_notificacao_familia')->nullable();
            $table->string('nome_responsavel_notificado')->nullable();
            $table->string('parentesco_responsavel')->nullable();
            
            // Autoridades (se necessário)
            $table->boolean('autoridades_notificadas')->default(false);
            $table->timestamp('data_notificacao_autoridades')->nullable();
            $table->text('motivo_notificacao_autoridades')->nullable();
            
            // Observações
            $table->text('observacoes')->nullable();
            
            // Encaminhamento do corpo
            $table->string('destino_corpo')->nullable(); // Funerária, IML, etc.
            $table->timestamp('data_liberacao_corpo')->nullable();
            $table->string('responsavel_retirada')->nullable();
            
            // Auditoria
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('consulta_id');
            $table->index('paciente_id');
            $table->index('medico_id');
            $table->index('data_hora_obito');
            $table->index('tipo_obito');
            $table->index('numero_declaracao_obito');
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
