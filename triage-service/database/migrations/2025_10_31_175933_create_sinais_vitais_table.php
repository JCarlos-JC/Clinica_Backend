<?php
// filepath: services/triage-service/database/migrations/2024_01_01_000002_create_sinais_vitais_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinais_vitais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triagem_id')
                  ->constrained('triagens')
                  ->onDelete('cascade');
            
            // Blood Pressure (Pressão Arterial)
            $table->string('pressao_arterial')->nullable(); // Ex: "120/80"
            $table->integer('pressao_arterial_sistolica')->nullable(); // 120
            $table->integer('pressao_arterial_diastolica')->nullable(); // 80
            
            // Heart Rate (Frequência Cardíaca)
            $table->integer('frequencia_cardiaca')->nullable(); // bpm
            
            // Temperature (Temperatura)
            $table->decimal('temperatura', 4, 1)->nullable(); // °C (Ex: 36.5)
            
            // Weight and Height (Peso e Altura)
            $table->decimal('peso', 6, 2)->nullable(); // kg (Ex: 70.50)
            $table->decimal('altura', 6, 2)->nullable(); // cm (Ex: 170.50)
            
            // BMI (IMC)
            $table->decimal('imc', 5, 2)->nullable(); // Calculated (Ex: 24.38)
            $table->string('classificacao_imc')->nullable(); // Classification
            
            // Oxygen Saturation (Oximetria)
            $table->integer('oximetria')->nullable(); // % (Ex: 98)
            
            // Capillary Blood Glucose (Glicemia Capilar)
            $table->integer('glicemia_capilar')->nullable(); // mg/dL (Ex: 100)
            
            // Additional vital signs (optional)
            $table->integer('frequencia_respiratoria')->nullable(); // rpm
            $table->integer('escala_dor')->nullable(); // 0-10
            
            $table->timestamps();
            
            // Indexes
            $table->index('triagem_id');
            $table->unique('triagem_id'); // One vital signs record per triage
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinais_vitais');
    }
};