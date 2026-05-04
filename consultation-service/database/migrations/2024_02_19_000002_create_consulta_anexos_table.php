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
        Schema::create('consulta_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
            $table->string('tipo')->comment('exame, imagem, documento, receita, atestado');
            $table->string('nome_arquivo');
            $table->string('caminho_arquivo');
            $table->string('mime_type')->nullable();
            $table->integer('tamanho')->nullable()->comment('Tamanho em bytes');
            $table->text('descricao')->nullable();
            $table->timestamps();
            
            $table->index(['consulta_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consulta_anexos');
    }
};
