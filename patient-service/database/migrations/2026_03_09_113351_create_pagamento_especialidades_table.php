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
        Schema::create('pagamento_especialidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('consulta_id');
            $table->unsignedBigInteger('agendamento_id')->nullable();
            $table->string('nid', 50);
            $table->string('especialidade_destino');
            $table->unsignedBigInteger('medico_destino_id')->nullable();
            $table->decimal('valor_consulta', 10, 2);
            $table->unsignedBigInteger('metodo_pagamento_id');
            $table->text('observacoes')->nullable();
            $table->enum('status_pagamento', ['pendente', 'confirmado', 'cancelado'])->default('confirmado');
            $table->timestamp('data_pagamento')->useCurrent();
            $table->timestamps();

            // Índices
            $table->index('paciente_id');
            $table->index('consulta_id');
            $table->index('agendamento_id');
            $table->index('nid');
            $table->index('status_pagamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamento_especialidades');
    }
};
