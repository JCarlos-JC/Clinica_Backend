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
        Schema::create('transferencia_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->enum('tipo', ['medico', 'especialidade']);
            $table->unsignedBigInteger('medico_origem_id')->nullable();
            $table->unsignedBigInteger('medico_destino_id');
            $table->string('especialidade_origem')->nullable();
            $table->string('especialidade_destino')->nullable();
            $table->text('motivo')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('data_transferencia');
            $table->timestamps();
            
            // Não usamos foreign keys para medico_id porque a tabela users está no authentication-service
            // Apenas criamos índices para performance
            $table->index('medico_origem_id');
            $table->index('medico_destino_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencia_historico');
    }
};
