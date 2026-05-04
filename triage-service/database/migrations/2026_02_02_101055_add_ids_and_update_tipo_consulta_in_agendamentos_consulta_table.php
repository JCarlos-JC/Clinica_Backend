<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agendamentos_consulta', function (Blueprint $table) {
            // Adicionar coluna especialidade_id
            $table->unsignedBigInteger('especialidade_id')->nullable()->after('especialidade');
            
            // Adicionar coluna tipo_consulta_id
            $table->unsignedBigInteger('tipo_consulta_id')->nullable()->after('tipo_consulta');
            
            // Adicionar índices para melhorar performance
            $table->index('especialidade_id');
            $table->index('tipo_consulta_id');
        });
        
        // Alterar tipo_consulta de ENUM para VARCHAR
        DB::statement("ALTER TABLE agendamentos_consulta MODIFY tipo_consulta VARCHAR(255) NOT NULL DEFAULT 'Consulta Regular'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos_consulta', function (Blueprint $table) {
            // Remover índices
            $table->dropIndex(['especialidade_id']);
            $table->dropIndex(['tipo_consulta_id']);
            
            // Remover colunas
            $table->dropColumn('especialidade_id');
            $table->dropColumn('tipo_consulta_id');
        });
        
        // Reverter tipo_consulta para ENUM
        DB::statement("ALTER TABLE agendamentos_consulta MODIFY tipo_consulta ENUM('Emergência','Muito Urgente','Urgente','Não Urgência') NOT NULL DEFAULT 'Não Urgência'");
    }
};
