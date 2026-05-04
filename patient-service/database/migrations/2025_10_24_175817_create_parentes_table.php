<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parentes', function (Blueprint $table) {
            $table->id();
            $table->string('paciente_nid', 20); // NID do paciente (ex: 0001/2025)
            $table->string('nome');
            $table->unsignedBigInteger('grau_parentesco_id')->nullable(); // Referência externa
            // $table->enum('grau_parentesco_nome', [
            //     'pai', 'mae', 'irmao', 'filho', 'conjuge', 
            //     'avo', 'tio', 'primo', 'outro'
            // ])->nullable();
            $table->string('celular', 20);
            $table->string('celular_alternativo', 20)->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('paciente_nid');
            
            // Foreign key para garantir integridade referencial
            $table->foreign('paciente_nid')
                  ->references('nid')
                  ->on('pacientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parentes');
    }
};