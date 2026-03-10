<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecoEspecialidade extends Model
{
    protected $table = 'preco_especialidades';

    protected $fillable = [
        'especialidade_id',
        'tipo_utente_id',
        'valor',
        'estado',
        'descricao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    /**
     * Relacionamento com Especialidade
     */
    public function especialidade()
    {
        return $this->belongsTo(Especialidade::class);
    }

    /**
     * Relacionamento com TipoUtente
     */
    public function tipoUtente()
    {
        return $this->belongsTo(TipoUtente::class);
    }

    /**
     * Scope para filtrar por estado ativo
     */
    public function scopeAtivo($query)
    {
        return $query->where('estado', 'Ativo');
    }

    /**
     * Scope para filtrar por especialidade
     */
    public function scopePorEspecialidade($query, $especialidadeId)
    {
        return $query->where('especialidade_id', $especialidadeId);
    }

    /**
     * Scope para filtrar por tipo de utente
     */
    public function scopePorTipoUtente($query, $tipoUtenteId)
    {
        return $query->where('tipo_utente_id', $tipoUtenteId);
    }
}
