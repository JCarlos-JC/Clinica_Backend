<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SolicitacaoTriagem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'solicitacoes_triagem';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'paciente_id',
        'estados_urgencia_id',
        'urgencia',
        'data_triagem',
        'data_solicitacao',
        'data_inicio_triagem',
        'data_conclusao_triagem',
        'data_cancelamento',
        'status',
        'ja_consultado',
        'resultados_exames',
        'retorno_consulta',
        'classificacao_risco',
        'prioridade_atendimento',
        'observacoes',
        'motivo_cancelamento',
        'triagem_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_triagem' => 'datetime',
        'data_solicitacao' => 'datetime',
        'data_inicio_triagem' => 'datetime',
        'data_conclusao_triagem' => 'datetime',
        'data_cancelamento' => 'datetime',
        'ja_consultado' => 'boolean',
        'resultados_exames' => 'array',
        'retorno_consulta' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the patient that owns the triage.
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
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
     * Scope a query to only include pending triages.
     */
    public function scopePendente($query)
    {
        return $query->where('status', 'pendente');
    }
    
    /**
     * Scope a query to only include waiting triages.
     */
    public function scopeAguardando($query)
    {
        return $query->where('status', 'aguardando_triagem');
    }

    /**
     * Scope a query to only include completed triages.
     */
    public function scopeConcluido($query)
    {
        return $query->where('status', 'concluido');
    }

    /**
     * Scope a query to filter by risk classification.
     */
    public function scopeClassificacaoRisco($query, $classificacao)
    {
        return $query->where('classificacao_risco', $classificacao);
    }

    /**
     * Scope a query to only include high priority triages.
     */
    public function scopeAltaPrioridade($query)
    {
        return $query->whereIn('classificacao_risco', ['vermelho', 'laranja']);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("
            CASE classificacao_risco
                WHEN 'vermelho' THEN 1
                WHEN 'laranja' THEN 2
                WHEN 'amarelo' THEN 3
                WHEN 'verde' THEN 4
                WHEN 'azul' THEN 5
                ELSE 6
            END
        ")->orderBy('prioridade_atendimento');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if triage is pending.
     */
    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    /**
     * Check if patient was already consulted.
     */
    public function foiConsultado(): bool
    {
        return $this->ja_consultado === true;
    }

    /**
     * Check if it's a return consultation.
     */
    public function isRetorno(): bool
    {
        return $this->retorno_consulta === true;
    }

    /**
     * Check if triage is critical (red or orange).
     */
    public function isCritico(): bool
    {
        return in_array($this->classificacao_risco, ['vermelho', 'laranja']);
    }

    /**
     * Mark as consulted.
     */
    public function marcarComoConsultado(): bool
    {
        $this->ja_consultado = true;
        $this->status = 'concluido';
        return $this->save();
    }

    /**
     * Get priority level based on risk classification.
     */
    public function getNivelPrioridade(): int
    {
        return match($this->classificacao_risco) {
            'vermelho' => 1,
            'laranja' => 2,
            'amarelo' => 3,
            'verde' => 4,
            'azul' => 5,
            default => 6,
        };
    }

    /**
     * Get color for risk classification.
     */
    public function getCorRisco(): string
    {
        return match($this->classificacao_risco) {
            'vermelho' => '#DC2626',
            'laranja' => '#EA580C',
            'amarelo' => '#FBBF24',
            'verde' => '#16A34A',
            'azul' => '#2563EB',
            default => '#6B7280',
        };
    }
}
