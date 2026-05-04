<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exame extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'precos_exames';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_exame_id',
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
     * Relacionamento com TipoExame
     */
    public function tipoExame()
    {
        return $this->belongsTo(TipoExame::class, 'tipo_exame_id');
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
     * Filtrar por tipo de exame
     */
    public function scopePorTipoExame($query, $tipoExameId)
    {
        return $query->where('tipo_exame_id', $tipoExameId);
    }

    /**
     * Filtrar por tipo de utente
     */
    public function scopePorTipoUtente($query, $tipoUtenteId)
    {
        return $query->where('tipo_utente_id', $tipoUtenteId);
    }

    /**
     * Filtrar por ativos
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Filtrar por inativos
     */
    public function scopeInativo($query)
    {
        return $query->where('ativo', false);
    }
}
