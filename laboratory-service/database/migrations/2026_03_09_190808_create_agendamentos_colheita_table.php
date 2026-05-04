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
        Schema::create('agendamentos_colheita', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitacao_exame_id')->nullable();
            $table->unsignedBigInteger('consulta_id')->nullable();
            $table->unsignedBigInteger('paciente_id');
            $table->string('nid', 50);
            $table->string('nome');
            $table->dateTime('data_colheita');
            $table->string('hora_colheita', 10);
            $table->enum('status', ['agendada', 'em_colheita', 'concluida', 'cancelada'])->default('agendada');
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('tecnico_id')->nullable();
            $table->string('hora_inicio', 10)->nullable();
            $table->string('hora_conclusao', 10)->nullable();
            $table->text('observacoes_colheita')->nullable();
            $table->text('observacoes_conclusao')->nullable();
            $table->dateTime('data_conclusao')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->dateTime('data_cancelamento')->nullable();
            $table->timestamps();
            
            $table->index('paciente_id');
            $table->index('nid');
            $table->index('status');
            $table->index('data_colheita');
        });

        Schema::create('exames_agendamento_colheita', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_colheita_id');
            $table->string('tipo_exame');
            $table->enum('prioridade', ['normal', 'urgente', 'critica'])->default('normal');
            $table->enum('status', ['agendado', 'em_colheita', 'concluido', 'cancelado'])->default('agendado');
            $table->json('resultado')->nullable();
            $table->text('laudo')->nullable();
            $table->json('valores_referencia')->nullable();
            $table->timestamps();
            
            $table->foreign('agendamento_colheita_id')->references('id')->on('agendamentos_colheita')->onDelete('cascade');
            $table->index('agendamento_colheita_id');
        });

        Schema::create('anexos_colheita', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_colheita_id');
            $table->unsignedBigInteger('exame_id')->nullable();
            $table->string('descricao')->nullable();
            $table->string('nome_original');
            $table->string('caminho');
            $table->string('tipo_mime');
            $table->bigInteger('tamanho');
            $table->timestamp('created_at')->nullable();
            
            $table->foreign('agendamento_colheita_id')->references('id')->on('agendamentos_colheita')->onDelete('cascade');
            $table->index('agendamento_colheita_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anexos_colheita');
        Schema::dropIfExists('exames_agendamento_colheita');
        Schema::dropIfExists('agendamentos_colheita');
    }
};
