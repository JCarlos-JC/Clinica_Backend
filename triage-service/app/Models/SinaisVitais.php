<?php
// filepath: services/triage-service/app/Models/SinaisVitais.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinaisVitais extends Model
{
    use HasFactory;

    protected $table = 'sinais_vitais';
    
    protected $fillable = [
        'triagem_id',
        'pressao_arterial',
        'pressao_arterial_sistolica',
        'pressao_arterial_diastolica',
        'frequencia_cardiaca',
        'temperatura',
        'peso',
        'altura',
        'imc',
        'classificacao_imc',
        'oximetria',
        'glicemia_capilar',
        'frequencia_respiratoria',
        'escala_dor',
    ];
    
    protected $casts = [
        'pressao_arterial_sistolica' => 'integer',
        'pressao_arterial_diastolica' => 'integer',
        'frequencia_cardiaca' => 'integer',
        'temperatura' => 'decimal:1',
        'peso' => 'decimal:2',
        'altura' => 'decimal:2',
        'imc' => 'decimal:2',
        'oximetria' => 'integer',
        'glicemia_capilar' => 'integer',
        'frequencia_respiratoria' => 'integer',
        'escala_dor' => 'integer',
    ];
    
    protected $appends = [
        'pressao_arterial_status',
        'temperatura_status',
        'glicemia_status',
        'oximetria_status'
    ];
    
    /**
     * Relationship with Triagem
     */
    public function triagem()
    {
        return $this->belongsTo(Triagem::class);
    }
    
    /**
     * Calculate and set IMC automatically
     * Matches: calcularIMC from TriagemPaciente.jsx
     */
    public function calcularIMC()
    {
        if ($this->peso && $this->altura && $this->altura > 0) {
            // Convert altura from cm to meters
            $alturaMetros = $this->altura / 100;
            $this->imc = round($this->peso / ($alturaMetros * $alturaMetros), 2);
            $this->classificacao_imc = $this->getClassificacaoIMC($this->imc);
        }
        
        return $this;
    }
    
    /**
     * Get IMC classification
     * Matches: getClassificacaoIMC from TriagemPaciente.jsx
     */
    public function getClassificacaoIMC($imc = null)
    {
        $imc = $imc ?? $this->imc;
        
        if (!$imc) return null;
        
        if ($imc < 18.5) return 'Abaixo do peso';
        if ($imc < 25) return 'Peso normal';
        if ($imc < 30) return 'Sobrepeso';
        if ($imc < 35) return 'Obesidade Grau I';
        if ($imc < 40) return 'Obesidade Grau II';
        return 'Obesidade Grau III';
    }
    
    /**
     * Parse blood pressure string (120/80) and set individual values
     */
    public function setPressaoArterialAttribute($value)
    {
        $this->attributes['pressao_arterial'] = $value;
        
        if ($value && strpos($value, '/') !== false) {
            $partes = explode('/', $value);
            $this->attributes['pressao_arterial_sistolica'] = isset($partes[0]) ? (int)$partes[0] : null;
            $this->attributes['pressao_arterial_diastolica'] = isset($partes[1]) ? (int)$partes[1] : null;
        }
    }
    
    /**
     * Get blood pressure status
     */
    public function getPressaoArterialStatusAttribute()
    {
        if (!$this->pressao_arterial_sistolica || !$this->pressao_arterial_diastolica) {
            return 'desconhecido';
        }
        
        $sistolica = $this->pressao_arterial_sistolica;
        $diastolica = $this->pressao_arterial_diastolica;
        
        if ($sistolica < 120 && $diastolica < 80) return 'normal';
        if ($sistolica < 130 && $diastolica < 85) return 'normal_alto';
        if ($sistolica < 140 || $diastolica < 90) return 'hipertensao_leve';
        if ($sistolica < 160 || $diastolica < 100) return 'hipertensao_moderada';
        return 'hipertensao_grave';
    }
    
    /**
     * Get temperature status
     */
    public function getTemperaturaStatusAttribute()
    {
        if (!$this->temperatura) return 'desconhecido';
        
        if ($this->temperatura < 35) return 'hipotermia';
        if ($this->temperatura < 36) return 'baixa';
        if ($this->temperatura <= 37.5) return 'normal';
        if ($this->temperatura <= 38) return 'febre_leve';
        if ($this->temperatura <= 39) return 'febre_moderada';
        return 'febre_alta';
    }
    
    /**
     * Get blood glucose status
     */
    public function getGlicemiaStatusAttribute()
    {
        if (!$this->glicemia_capilar) return 'desconhecido';
        
        if ($this->glicemia_capilar < 70) return 'hipoglicemia';
        if ($this->glicemia_capilar <= 100) return 'normal';
        if ($this->glicemia_capilar <= 125) return 'pre_diabetes';
        return 'hiperglicemia';
    }
    
    /**
     * Get oxygen saturation status
     */
    public function getOximetriaStatusAttribute()
    {
        if (!$this->oximetria) return 'desconhecido';
        
        if ($this->oximetria < 90) return 'critico';
        if ($this->oximetria < 95) return 'baixo';
        return 'normal';
    }
    
    /**
     * Check if vital signs are critical
     */
    public function isEstadoCritico()
    {
        $critico = false;
        
        // Critical blood pressure
        if ($this->pressao_arterial_sistolica > 180 || $this->pressao_arterial_diastolica > 120) {
            $critico = true;
        }
        
        // Critical temperature
        if ($this->temperatura < 35 || $this->temperatura > 40) {
            $critico = true;
        }
        
        // Critical oxygen saturation
        if ($this->oximetria < 90) {
            $critico = true;
        }
        
        // Critical heart rate
        if ($this->frequencia_cardiaca < 40 || $this->frequencia_cardiaca > 140) {
            $critico = true;
        }
        
        return $critico;
    }
    
    /**
     * Get vital signs summary for consultation service
     */
    public function getSummary()
    {
        return [
            'pressao_arterial' => [
                'valor' => $this->pressao_arterial,
                'sistolica' => $this->pressao_arterial_sistolica,
                'diastolica' => $this->pressao_arterial_diastolica,
                'status' => $this->pressao_arterial_status,
            ],
            'frequencia_cardiaca' => [
                'valor' => $this->frequencia_cardiaca,
                'unidade' => 'bpm',
            ],
            'temperatura' => [
                'valor' => $this->temperatura,
                'unidade' => '°C',
                'status' => $this->temperatura_status,
            ],
            'peso' => [
                'valor' => $this->peso,
                'unidade' => 'kg',
            ],
            'altura' => [
                'valor' => $this->altura,
                'unidade' => 'cm',
            ],
            'imc' => [
                'valor' => $this->imc,
                'classificacao' => $this->classificacao_imc,
            ],
            'oximetria' => [
                'valor' => $this->oximetria,
                'unidade' => '%',
                'status' => $this->oximetria_status,
            ],
            'glicemia_capilar' => [
                'valor' => $this->glicemia_capilar,
                'unidade' => 'mg/dL',
                'status' => $this->glicemia_status,
            ],
            'estado_critico' => $this->isEstadoCritico(),
        ];
    }
}