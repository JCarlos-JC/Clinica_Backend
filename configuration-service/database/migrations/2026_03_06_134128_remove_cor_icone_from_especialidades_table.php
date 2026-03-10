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
        Schema::table('especialidades', function (Blueprint $table) {
            if (Schema::hasColumn('especialidades', 'cor')) {
                $table->dropColumn('cor');
            }
            if (Schema::hasColumn('especialidades', 'icone')) {
                $table->dropColumn('icone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('especialidades', function (Blueprint $table) {
            $table->string('cor', 20)->nullable()->comment('Código de cor para interface');
            $table->string('icone', 50)->nullable()->comment('Nome do ícone para interface');
        });
    }
};
