<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_organica', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->unique();
            $table->string('sigla', 20)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->string('tipo', 50)->nullable()->comment('faculdade, escola, departamento, etc');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_organica');
    }
};