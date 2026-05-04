<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SolicitacaoExame extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'solicitacoes_exames';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consulta_id',
        'agendamento_id',
        'paciente_id',
        'nid',
        'nome',
        'paciente_nid',
        'utente_autonomo_nid',
        'solicitante_id',
        'medico_nome',
        'especialidade',
        'data_solicitacao',
        'queixa_principal',
        'historico',
        'exame_fisico',
        'hipotese_diagnostica',
        'exames',
        'exames_solicitados',
        'exames_realizaveis',
        'exames_nao_realizaveis',
        'status',
        'valor_total',
        'data_confirmacao',
        'valor_pago',
        'metodo_pagamento',
        'referencia_pagamento',
        'data_pagamento',
        'data_agendamento_colheita',
        'hora_colheita',
        'motivo_cancelamento',
        'data_cancelamento',
        'tipos_exame_id',
        'metodo_pagamento_id',
        'observacoes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exames' => 'array',
        'exames_solicitados' => 'array',
        'exames_realizaveis' => 'array',
        'exames_nao_realizaveis' => 'array',
        'data_solicitacao' => 'datetime',
        'data_confirmacao' => 'datetime',
        'data_pagamento' => 'datetime',
        'data_agendamento_colheita' => 'date',
        'data_cancelamento' => 'datetime',
        'valor_total' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the patient that owns the exam request.
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_nid', 'nid');
    }

    /**
     * Get the autonomous user that owns the exam request.
     */
    public function utenteAutonomo()
    {
        return $this->belongsTo(UtenteAutonomo::class, 'utente_autonomo_nid', 'nid');
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
     * Scope a query to only include completed requests.
     */
    public function scopeConcluido($query)
    {
        return $query->where('status', 'concluido');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if request is pending.
     */
    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    /**
     * Check if request is paid.
     */
    public function isPago(): bool
    {
        return in_array($this->status, ['pago', 'em_laboratorio', 'concluido']);
    }

    /**
     * Check if request is completed.
     */
    public function isConcluido(): bool
    {
        return $this->status === 'concluido';
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
     * Mark as in laboratory.
     */
    public function enviarParaLaboratorio(): bool
    {
        $this->status = 'em_laboratorio';
        return $this->save();
    }

    /**
     * Mark as completed.
     */
    public function concluir(): bool
    {
        $this->status = 'concluido';
        return $this->save();
    }

    /**
     * Cancel the request.
     */
    public function cancelar(): bool
    {
        $this->status = 'cancelado';
        return $this->save();
    }
}
