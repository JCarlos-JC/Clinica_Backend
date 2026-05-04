<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoConsulta extends Model
{
    use HasFactory;
    
    protected $table = 'tipos_consulta';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'requer_triagem',
        'ativo'
    ];
    
    protected $casts = [
        'requer_triagem' => 'boolean',
        'ativo' => 'boolean',
    ];

    /**
     * Relação com PrecoConsulta (preços específicos por tipo de utente)
     */
    public function precos()
    {
        return $this->hasMany(PrecoConsulta::class, 'tipo_consulta_id');
    }

    /**
     * Obter preço específico para um tipo de utente
     */
    public function getPrecoParaUtente($tipoUtenteId)
    {
        return $this->precos()
            ->where('tipo_utente_id', $tipoUtenteId)
            ->where('ativo', true)
            ->first();
    }
}
