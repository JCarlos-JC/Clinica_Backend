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
        DB::statement("ALTER TABLE consultas MODIFY COLUMN status ENUM('agendada','em_atendimento','finalizada','cancelada','nao_compareceu','remarcada','transferido_especialidade','aguardando_exames','retorno_exames') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE consultas MODIFY COLUMN status ENUM('agendada','em_atendimento','finalizada','cancelada','nao_compareceu','remarcada','transferido_especialidade') NOT NULL");
    }
};
