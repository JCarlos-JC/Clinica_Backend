<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primeiro adicionar as colunas
        Schema::table('solicitacoes_exames', function (Blueprint $table) {
            // Campos de consulta e agendamento
            $table->unsignedBigInteger('consulta_id')->nullable()->after('id');
            $table->unsignedBigInteger('agendamento_id')->nullable()->after('consulta_id');
            
            // Campos do paciente expandidos
            $table->unsignedBigInteger('paciente_id')->nullable()->after('agendamento_id');
            $table->string('nid', 20)->nullable()->after('paciente_id');
            $table->string('nome')->nullable()->after('nid');
            
            // Informações do médico
            $table->string('medico_nome')->nullable()->after('solicitante_id');
            $table->string('especialidade')->nullable()->after('medico_nome');
            
            // Dados clínicos
            $table->text('queixa_principal')->nullable()->after('data_solicitacao');
            $table->text('historico')->nullable()->after('queixa_principal');
            $table->text('exame_fisico')->nullable()->after('historico');
            $table->text('hipotese_diagnostica')->nullable()->after('exame_fisico');
            
            // Novo campo para armazenar exames no formato da API
            $table->json('exames')->nullable()->after('exames_solicitados');
            
            // Campos de pagamento expandidos
            $table->decimal('valor_total', 10, 2)->nullable()->after('status');
            $table->datetime('data_confirmacao')->nullable()->after('valor_total');
            $table->decimal('valor_pago', 10, 2)->nullable()->after('data_confirmacao');
            $table->string('metodo_pagamento')->nullable()->after('valor_pago');
            $table->string('referencia_pagamento')->nullable()->after('metodo_pagamento');
            
            // Campos de agendamento de colheita
            $table->date('data_agendamento_colheita')->nullable()->after('data_pagamento');
            $table->time('hora_colheita')->nullable()->after('data_agendamento_colheita');
            
            // Campos de cancelamento
            $table->text('motivo_cancelamento')->nullable()->after('observacoes');
            $table->datetime('data_cancelamento')->nullable()->after('motivo_cancelamento');
        });
        
        // Primeiro expandir o ENUM para incluir todos os valores
        DB::statement("ALTER TABLE solicitacoes_exames MODIFY COLUMN status ENUM('pendente','aceito','pago','em_laboratorio','concluido','cancelado','confirmada','paga','agendada','em_colheita','concluida','cancelada') NOT NULL DEFAULT 'pendente'");
        
        // Atualizar os valores antigos para os novos
        DB::statement("UPDATE solicitacoes_exames SET status = 'confirmada' WHERE status = 'aceito'");
        DB::statement("UPDATE solicitacoes_exames SET status = 'em_colheita' WHERE status = 'em_laboratorio'");
        DB::statement("UPDATE solicitacoes_exames SET status = 'concluida' WHERE status = 'concluido'");
        
        // Finalmente remover os valores antigos do ENUM
        DB::statement("ALTER TABLE solicitacoes_exames MODIFY COLUMN status ENUM('pendente','confirmada','paga','agendada','em_colheita','concluida','cancelada') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        Schema::table('solicitacoes_exames', function (Blueprint $table) {
            $table->dropColumn([
                'consulta_id',
                'agendamento_id',
                'paciente_id',
                'nid',
                'nome',
                'medico_nome',
                'especialidade',
                'queixa_principal',
                'historico',
                'exame_fisico',
                'hipotese_diagnostica',
                'exames',
                'valor_total',
                'data_confirmacao',
                'valor_pago',
                'metodo_pagamento',
                'referencia_pagamento',
                'data_agendamento_colheita',
                'hora_colheita',
                'motivo_cancelamento',
                'data_cancelamento'
            ]);
            
            DB::statement("ALTER TABLE solicitacoes_exames MODIFY COLUMN status ENUM('pendente','aceito','pago','em_laboratorio','concluido','cancelado') NOT NULL DEFAULT 'pendente'");
        });
    }
};
