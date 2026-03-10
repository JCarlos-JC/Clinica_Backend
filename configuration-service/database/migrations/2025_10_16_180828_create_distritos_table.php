<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distritos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('codigo', 20)->nullable();
            $table->foreignId('provincia_id')->constrained('provincias');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Unique constraint for nome within the same provincia
            $table->unique(['nome', 'provincia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distritos');
    }
};