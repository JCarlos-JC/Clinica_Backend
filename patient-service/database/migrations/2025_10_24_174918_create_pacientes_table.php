<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nid')->nullable()->unique();
            
            // Informações Pessoais
            $table->string('nome');
            $table->string('apelido');
            $table->date('data_nascimento');
            $table->enum('genero', ['masculino', 'feminino', 'outro']);
            $table->enum('estado_civil', ['solteiro', 'casado', 'divorciado', 'viuvo'])->nullable();
            
            // Raça e Nacionalidade
            $table->unsignedBigInteger('raca_id')->nullable(); // Referência externa
            // $table->enum('raca', ['negra', 'branca', 'mista', 'asiatica', 'outro'])->nullable();
            $table->string('nacionalidade')->default('Mocambicana');
            
            // Tipo de Utente (referência externa ao configuration-service)
            $table->unsignedBigInteger('tipo_utente_id')->nullable(); // Referência externa
            
            // Campos específicos por tipo de utente
            $table->unsignedBigInteger('unidade_organica_id')->nullable(); // Referência externa
            
            // Informações de Familiar
            $table->string('nome_familiar')->nullable();
            
            // Informações de Contato
            $table->string('celular', 20);
            $table->string('celular_alternativo', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();
            
            // Endereço (referências externas ao configuration-service)
            $table->unsignedBigInteger('provincia_id')->nullable(); // Referência externa
            $table->unsignedBigInteger('distrito_id')->nullable(); // Referência externa
            $table->unsignedBigInteger('bairro_id')->nullable(); // Referência externa
            $table->string('avenida_rua_celula')->nullable();
            $table->string('numero_casa')->nullable();
            $table->string('quarteirao')->nullable();
            
            // Documento
            $table->unsignedBigInteger('tipo_documento_id')->nullable(); // Referência externa
            $table->string('bilhete_identidade')->nullable(); // Número do bilhete de identidade
            $table->string('documento_path')->nullable(); // Caminho do arquivo anexado
            
            // Status do Paciente
            $table->enum('status', [
                'ativo', 
                'inativo', 
                'alta', 
                'obito', 
                'transferencia', 
                'transferido_especialidade',
                'em_consulta',
                'aguardando_triagem'
            ])->default('ativo');
            
            // Informações de Transferência para Especialidade
            $table->string('hospital_proveniencia')->nullable();
            $table->string('especialidade_anterior')->nullable();
            $table->string('especialidade')->nullable();
            $table->string('medico')->nullable();
            $table->dateTime('data_transferencia')->nullable();
            $table->text('motivo')->nullable();
            
            // Informações de Pagamento
            $table->enum('status_pagamento', ['pendente', 'pago', 'isento'])->nullable();
            $table->unsignedBigInteger('tipo_consulta_id')->nullable(); // Referência externa
            // $table->decimal('valor_consulta', 10, 2)->nullable();
            $table->unsignedBigInteger('metodo_pagamento_id')->nullable(); // Referência externa
            $table->dateTime('data_pagamento')->nullable();
            
            // Informações de Acompanhamento
            $table->boolean('tem_acompanhamento_disponivel')->default(false);
            $table->timestamp('data_limite_acompanhamento')->nullable();
            $table->timestamp('ultimo_ciclo_terminado')->nullable();
            
            $table->text('observacoes')->nullable();
            $table->timestamps();

            
            // Índices
            $table->index('nome');
            $table->index('apelido');
            $table->index('status');
            $table->index('genero');
            $table->index('tipo_utente_id');
            $table->index('data_nascimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};