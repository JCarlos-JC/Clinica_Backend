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
        // Remover colunas obsoletas de tipos_consulta
        Schema::table('tipos_consulta', function (Blueprint $table) {
            $table->dropColumn(['isento_pagamento', 'preco_padrao']);
        });

        // Remover colunas obsoletas de tipo_utentes
        Schema::table('tipo_utentes', function (Blueprint $table) {
            $table->dropColumn(['isento_pagamento', 'percentual_desconto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar colunas em tipos_consulta
        Schema::table('tipos_consulta', function (Blueprint $table) {
            $table->boolean('isento_pagamento')->default(false);
            $table->decimal('preco_padrao', 10, 2)->default(0.00);
        });

        // Restaurar colunas em tipo_utentes
        Schema::table('tipo_utentes', function (Blueprint $table) {
            $table->boolean('isento_pagamento')->default(false);
            $table->decimal('percentual_desconto', 5, 2)->default(0.00);
        });
    }
};
