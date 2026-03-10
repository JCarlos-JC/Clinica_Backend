<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcao_especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->boolean('pode_prescrever')->default(false);
            $table->boolean('pode_solicitar_exames')->default(false);
            $table->boolean('pode_criar_prontuario')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcao_especialidades');
    }
};