<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtenteAutonomo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'utentes_autonomos';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (UtenteAutonomo $utente) {
            if (empty($utente->nid)) {
                $utente->nid = static::gerarNID();
            }

        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nid',
        'nome',
        'apelido',
        'data_nascimento',
        'genero',
        'tipo_documento_id',
        'bilhete_identidade',
        'celular',
        'celular_alternativo',
        'email',
        'hospital_proveniencia',
        'exames_solicitados',
        'data_solicitacao',
        'status',
        'tipos_exame_id',
        'metodo_pagamento_id',
        'data_pagamento',
        'resultados_exames',
        'data_resultados',
        'data_exames',
        'historico_exames',
        'observacoes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_nascimento' => 'date',
        'exames_solicitados' => 'array',
        'data_solicitacao' => 'datetime',
        'data_pagamento' => 'datetime',
        'resultados_exames' => 'array',
        'data_resultados' => 'datetime',
        'data_exames' => 'datetime',
        'historico_exames' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to model arrays.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'nome_completo',
    ];

    // ==================== ACCESSORS ====================

    /**
     * Get the autonomous user's full name.
     */
    public function getNomeCompletoAttribute(): string
    {
        return "{$this->nome} {$this->apelido}";
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePendente($query)
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Scope a query to only include accepted requests.
     */
    public function scopeAceito($query)
    {
        return $query->where('status', 'aceito');
    }

    /**
     * Scope a query to only include paid requests.
     */
    public function scopePago($query)
    {
        return $query->where('status', 'pago');
    }

    /**
     * Scope a query to search by name or code.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nome', 'like', "%{$search}%")
              ->orWhere('apelido', 'like', "%{$search}%")
              ->orWhere('nid', 'like', "%{$search}%")
              ->orWhere('celular', 'like', "%{$search}%");
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the history records for the autonomous user.
     */
    // public function historico()
    // {
    //     return $this->hasMany(HistoricoPaciente::class, 'utente_autonomo_id');
    // }

    // ==================== HELPER METHODS ====================

    /**
     * Check if request is pending.
     */
    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    /**
     * Check if request is accepted.
     */
    public function isAceito(): bool
    {
        return $this->status === 'aceito';
    }

    /**
     * Check if payment is completed.
     */
    public function isPago(): bool
    {
        return in_array($this->status, ['pago', 'pago_laboratorio']);
    }

    /**
     * Mark as accepted.
     */
    public function aceitar(): bool
    {
        $this->status = 'aceito';
        return $this->save();
    }

    /**
     * Mark as paid.
     */
    public function marcarComoPago(): bool
    {
        $this->status = 'pago';
        $this->data_pagamento = now();
        return $this->save();
    }

    /**
     * Mark as completed.
     */
    public function concluir(): bool
    {
        $this->status = 'concluido';
        $this->data_resultados = now();
        return $this->save();
    }

    // ==================== NID GENERATION ====================

    /**
     * Generate a unique NID for the autonomous user.
     * Format: UT000/ANO (e.g., UT001/2025)
     */
    public static function gerarNID(): string
    {
        $ano = now()->year;
        
        // Buscar o último NID do ano atual
        $ultimoUtente = static::whereYear('created_at', $ano)
            ->whereNotNull('nid')
            ->where('nid', 'like', "UT%/{$ano}")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($ultimoUtente && $ultimoUtente->nid) {
            // Extrair o número sequencial do NID (ex: "UT001/2025" -> 1)
            $partes = explode('/', $ultimoUtente->nid);
            $prefixoNumero = str_replace('UT', '', $partes[0]);
            $numeroAtual = (int) $prefixoNumero;
            $proximoNumero = $numeroAtual + 1;
        } else {
            // Primeiro utente autônomo do ano
            $proximoNumero = 1;
        }
        
        // Formatar com 3 dígitos: UT001, UT002, etc.
        $numeroFormatado = str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
        
        return "UT{$numeroFormatado}/{$ano}";
    }

    /**
     * Get the next available NID for the current year.
     */
    public static function proximoNID(): string
    {
        return static::gerarNID();
    }
}
