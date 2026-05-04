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
        // First, alter the enum to include all possible statuses (including 'concluida' temporarily)
        DB::statement("ALTER TABLE solicitacoes_triagem MODIFY COLUMN status ENUM('aguardando_triagem', 'em_triagem', 'triagem_concluida', 'cancelada', 'concluida') NOT NULL DEFAULT 'aguardando_triagem'");
        
        // Update existing 'concluida' to 'triagem_concluida'
        DB::table('solicitacoes_triagem')
            ->where('status', 'concluida')
            ->update(['status' => 'triagem_concluida']);
        
        // Remove 'concluida' from enum
        DB::statement("ALTER TABLE solicitacoes_triagem MODIFY COLUMN status ENUM('aguardando_triagem', 'em_triagem', 'triagem_concluida', 'cancelada') NOT NULL DEFAULT 'aguardando_triagem'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'triagem_concluida' back to 'concluida'
        DB::table('solicitacoes_triagem')
            ->where('status', 'triagem_concluida')
            ->update(['status' => 'concluida']);
            
        // Revert enum to original state
        DB::statement("ALTER TABLE solicitacoes_triagem MODIFY COLUMN status ENUM('aguardando_triagem', 'concluida') NOT NULL DEFAULT 'aguardando_triagem'");
    }
};
