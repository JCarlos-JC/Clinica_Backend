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
        Schema::table('agendamentos_consulta', function (Blueprint $table) {
            // Adicionar campo tipo se não existir
            if (!Schema::hasColumn('agendamentos_consulta', 'tipo')) {
                $table->enum('tipo', ['triagem', 'transferencia_medico', 'transferencia_especialidade'])
                      ->default('triagem')
                      ->after('status')
                      ->comment('Tipo de agendamento');
            }
            
            // Adicionar campo status_pagamento se não existir
            if (!Schema::hasColumn('agendamentos_consulta', 'status_pagamento')) {
                $table->enum('status_pagamento', ['pendente', 'pago', 'cancelado'])
                      ->nullable()
                      ->after('tipo')
                      ->comment('Status de pagamento do agendamento');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos_consulta', function (Blueprint $table) {
            if (Schema::hasColumn('agendamentos_consulta', 'tipo')) {
                $table->dropColumn('tipo');
            }
            
            if (Schema::hasColumn('agendamentos_consulta', 'status_pagamento')) {
                $table->dropColumn('status_pagamento');
            }
        });
    }
};
