<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('principio_ativo', 255);
            $table->string('codigo', 50)->nullable()->unique();
            $table->foreignId('forma_id')->constrained('formas_medicamento');
            $table->foreignId('via_administracao_id')->constrained('vias_administracao');
            $table->string('dosagem', 50);
            $table->string('unidade_dosagem', 20);
            $table->text('instrucoes_padrao')->nullable();
            $table->text('contraindicacoes')->nullable();
            $table->text('efeitos_colaterais')->nullable();
            $table->boolean('controlado')->default(false);
            $table->boolean('generico')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Compound unique constraint
            $table->unique(['nome', 'principio_ativo', 'forma_id', 'dosagem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};