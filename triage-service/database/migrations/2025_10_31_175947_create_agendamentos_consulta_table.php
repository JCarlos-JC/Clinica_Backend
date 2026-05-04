<?php
// filepath: services/triage-service/database/migrations/2024_01_01_000003_create_agendamentos_consulta_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos_consulta', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_agendamento')->unique();
            
            // Relationship with triage
            $table->foreignId('triagem_id')
                  ->constrained('triagens')
                  ->onDelete('cascade');
            
            // Patient reference (from patient-service)
            $table->unsignedBigInteger('paciente_id');
            $table->string('nid')->nullable();
            $table->string('nome')->nullable();
            $table->string('apelido')->nullable();
            $table->string('genero')->nullable();
            $table->date('data_nascimento')->nullable();
            
            // Consultation details
            $table->string('especialidade')->default('Clínico Geral');
            $table->string('medico'); // Doctor name
            $table->unsignedBigInteger('medico_id')->nullable(); // Doctor ID from auth-service
            
            // Type of consultation based on urgency (from TriagemPaciente.jsx)
            $table->enum('tipo_consulta', [
                'Emergência',
                'Muito Urgente',
                'Urgente',
                'Não Urgência'
            ])->default('Não Urgência');
            
            // Scheduling information
            $table->date('data_consulta');
            $table->time('hora_consulta');
            $table->text('motivo_consulta');
            $table->text('observacoes')->nullable();
            
            // Timestamps
            $table->timestamp('data_agendamento'); // When scheduled
            $table->timestamp('data_confirmacao')->nullable(); // When confirmed
            $table->timestamp('data_cancelamento')->nullable(); // If cancelled
            
            // Status
            $table->enum('status', [
                'agendado',
                'confirmada',
                'em_atendimento',
                'concluida',
                'cancelada',
                'paciente_faltou'
            ])->default('agendado');
            
            // Reference to consultation in consultation-service
            $table->unsignedBigInteger('consulta_id')->nullable(); // ID from consultation-service
            $table->boolean('enviado_consultation_service')->default(false);
            $table->timestamp('data_envio_consultation_service')->nullable();
            
            // Cancellation info
            $table->string('motivo_cancelamento')->nullable();
            $table->unsignedBigInteger('cancelado_por')->nullable();
            
            // Priority based on triage urgency
            $table->enum('prioridade', [
                'emergencia',
                'urgente',
                'normal'
            ])->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('triagem_id');
            $table->index('paciente_id');
            $table->index('medico_id');
            $table->index('data_consulta');
            $table->index('status');
            $table->index('tipo_consulta');
            $table->index(['data_consulta', 'hora_consulta']);
            $table->index(['status', 'data_consulta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos_consulta');
    }
};