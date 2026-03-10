<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecoConsulta extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'precos_consultas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_consulta_id',
        'tipo_utente_id',
        'valor',
        'descricao',
        'ativo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor' => 'decimal:2',
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Relacionamento com TipoConsulta
     */
    public function tipoConsulta()
    {
        return $this->belongsTo(TipoConsulta::class, 'tipo_consulta_id');
    }

    /**
     * Relacionamento com TipoUtente
     */
    public function tipoUtente()
    {
        return $this->belongsTo(TipoUtente::class, 'tipo_utente_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope para buscar apenas preços ativos
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para buscar por tipo de consulta
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tipoConsultaId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorTipoConsulta($query, int $tipoConsultaId)
    {
        return $query->where('tipo_consulta_id', $tipoConsultaId);
    }

    /**
     * Scope para buscar por tipo de utente
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tipoUtenteId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorTipoUtente($query, int $tipoUtenteId)
    {
        return $query->where('tipo_utente_id', $tipoUtenteId);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get formatted valor
     * 
     * @return string
     */
    public function getValorFormatadoAttribute(): string
    {
        return number_format($this->valor, 2, ',', '.') . ' MZN';
    }

    // ==================== METHODS ====================

    /**
     * Ativar o preço
     * 
     * @return bool
     */
    public function ativar(): bool
    {
        return $this->update(['ativo' => true]);
    }

    /**
     * Desativar o preço
     * 
     * @return bool
     */
    public function desativar(): bool
    {
        return $this->update(['ativo' => false]);
    }
}
