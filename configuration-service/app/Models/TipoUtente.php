<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoUtente extends Model
{
    use HasFactory;
    
    protected $table = 'tipo_utentes';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Relação com PrecoConsulta (preços específicos por tipo de consulta)
     */
    public function precos()
    {
        return $this->hasMany(PrecoConsulta::class, 'tipo_utente_id');
    }

    /**
     * Obter preço específico para um tipo de consulta
     */
    public function getPrecoParaConsulta($tipoConsultaId)
    {
        return $this->precos()
            ->where('tipo_consulta_id', $tipoConsultaId)
            ->where('ativo', true)
            ->first();
    }
}