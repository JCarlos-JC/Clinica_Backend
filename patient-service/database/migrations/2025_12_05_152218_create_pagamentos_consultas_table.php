<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Criar tabela para registrar pagamentos de consultas
     * 
     * Benefícios desta abordagem:
     * - Histórico completo de todos os pagamentos
     * - Suporta múltiplas consultas por paciente
     * - Permite auditoria e rastreabilidade
     * - Facilita relatórios financeiros
     * - Monitoramento de ciclos de consulta
     */
    public function up(): void
    {
        Schema::create('pagamentos_consultas', function (Blueprint $table) {
            $table->id();
            
            // Referência ao paciente
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->onDelete('cascade');
            $table->string('paciente_nid')->nullable(); // Para facilitar buscas
            
            // Informações do Pagamento
            $table->unsignedBigInteger('tipo_consulta_id'); // Referência externa (configuration-service)
            $table->unsignedBigInteger('metodo_pagamento_id'); // Referência externa (configuration-service)
            
            // Valores
            $table->decimal('valor_original', 10, 2); // Valor da tabela preco_consultas
            $table->decimal('desconto', 10, 2)->default(0); // Desconto aplicado
            $table->decimal('valor_pago', 10, 2); // Valor efetivamente pago
            
            // Status e Tipo
            $table->enum('status', [
                'pendente',    // Aguardando pagamento
                'pago',        // Pagamento confirmado
                'isento',      // Isenção (ex: estudante bolseiro)
                'cancelado',   // Pagamento cancelado
                'reembolsado'  // Pagamento reembolsado
            ])->default('pendente');
            
            $table->enum('tipo_pagamento', [
                'consulta_regular',
                'consulta_urgencia',
                'consulta_especialidade',
                'retorno',
                'acompanhamento'
            ])->default('consulta_regular');
            
            // Informações de Isenção
            $table->boolean('isencao_aplicada')->default(false);
            $table->string('motivo_isencao')->nullable(); // Ex: "Estudante Bolseiro", "Paciente Especial"
            
            // Datas
            $table->timestamp('data_pagamento')->nullable(); // Quando foi pago
            $table->timestamp('data_vencimento')->nullable(); // Para pagamentos pendentes
            
            // Auditoria
            $table->unsignedBigInteger('usuario_id')->nullable(); // Quem registrou o pagamento
            $table->string('usuario_nome')->nullable(); // Nome do usuário (redundância para histórico)
            
            // Informações Adicionais
            $table->string('numero_recibo')->nullable()->unique(); // Número do recibo gerado
            $table->string('numero_referencia')->nullable(); // Referência bancária (se aplicável)
            $table->text('observacoes')->nullable();
            
            // Ciclo de Consulta (para controle de retornos)
            $table->foreignId('pagamento_anterior_id')
                ->nullable()
                ->constrained('pagamentos_consultas')
                ->onDelete('set null'); // Referência ao pagamento anterior (para retornos)
            
            $table->boolean('permite_retorno')->default(false); // Se este pagamento permite retorno grátis
            $table->timestamp('data_limite_retorno')->nullable(); // Limite para retorno grátis
            $table->integer('dias_validade_retorno')->nullable(); // Dias de validade do retorno
            
            // Soft Deletes para auditoria
            $table->softDeletes();
            
            $table->timestamps();
            
            // Índices para otimização de consultas
            $table->index('paciente_id');
            $table->index('paciente_nid');
            $table->index('status');
            $table->index('tipo_pagamento');
            $table->index('data_pagamento');
            $table->index('numero_recibo');
            $table->index('tipo_consulta_id');
            $table->index(['paciente_id', 'data_pagamento']); // Para histórico do paciente
            $table->index(['status', 'data_pagamento']); // Para relatórios financeiros
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos_consultas');
    }
};
