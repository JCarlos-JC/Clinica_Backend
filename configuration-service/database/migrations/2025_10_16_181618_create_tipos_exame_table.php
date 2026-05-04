<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_exame', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->string('categoria', 50)->nullable()->comment('laboratorial, imagem, etc');
            $table->decimal('preco_padrao', 10, 2)->default(0.00);
            $table->integer('tempo_estimado_minutos')->nullable();
            $table->boolean('requer_jejum')->default(false);
            $table->text('instrucoes_preparo')->nullable();
            $table->text('instrucoes_coleta')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_exame');
    }
};