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
        Schema::table('pacientes', function (Blueprint $table) {
            // Adicionar campo documento após bilhete_identidade
            $table->string('documento', 255)->nullable()->after('bilhete_identidade');
            
            // Adicionar unidade_organica_familiar após nome_familiar
            $table->unsignedBigInteger('unidade_organica_familiar')->nullable()->after('nome_familiar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['documento', 'unidade_organica_familiar']);
        });
    }
};
