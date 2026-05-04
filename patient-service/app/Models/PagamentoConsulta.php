<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para gerenciar pagamentos de consultas
 * 
 * Este modelo substitui o armazenamento direto de informações de pagamento
 * na tabela pacientes, permitindo:
 * - Histórico completo de pagamentos
 * - Múltiplas consultas por paciente
 * - Auditoria e rastreabilidade
 * - Relatórios financeiros
 * - Controle de ciclos e retornos
 */
class PagamentoConsulta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pagamentos_consultas';

    protected $fillable = [
        'paciente_id',
        'paciente_nid',
        'tipo_consulta_id',
        'metodo_pagamento_id',
        'valor_original',
        'desconto',
        'valor_pago',
        'status',
        'tipo_pagamento',
        'isencao_aplicada',
        'motivo_isencao',
        'data_pagamento',
        'data_vencimento',
        'usuario_id',
        'usuario_nome',
        'numero_recibo',
        'numero_referencia',
        'observacoes',
        'pagamento_anterior_id',
        'permite_retorno',
        'data_limite_retorno',
        'dias_validade_retorno',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'isencao_aplicada' => 'boolean',
        'permite_retorno' => 'boolean',
        'data_pagamento' => 'datetime',
        'data_vencimento' => 'datetime',
        'data_limite_retorno' => 'datetime',
    ];

    /**
     * Relacionamentos
     */
    
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function pagamentoAnterior(): BelongsTo
    {
        return $this->belongsTo(PagamentoConsulta::class, 'pagamento_anterior_id');
    }

    public function retornos()
    {
        return $this->hasMany(PagamentoConsulta::class, 'pagamento_anterior_id');
    }

    /**
     * Scopes para facilitar consultas
     */
    
    public function scopePagos($query)
    {
        return $query->where('status', 'pago');
    }

    public function scopeIsentos($query)
    {
        return $query->where('status', 'isento');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeCancelados($query)
    {
        return $query->where('status', 'cancelado');
    }

    public function scopeConsultasRegulares($query)
    {
        return $query->where('tipo_pagamento', 'consulta_regular');
    }

    public function scopeRetornos($query)
    {
        return $query->where('tipo_pagamento', 'retorno');
    }

    public function scopePorPaciente($query, $pacienteId)
    {
        return $query->where('paciente_id', $pacienteId);
    }

    public function scopePorNid($query, $nid)
    {
        return $query->where('paciente_nid', $nid);
    }

    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_pagamento', [$dataInicio, $dataFim]);
    }

    public function scopeComRetornoDisponivel($query)
    {
        return $query->where('permite_retorno', true)
            ->where('data_limite_retorno', '>=', now());
    }

    /**
     * Métodos auxiliares
     */
    
    /**
     * Gerar número de recibo único
     */
    public static function gerarNumeroRecibo(): string
    {
        $ano = now()->year;
        $mes = now()->format('m');
        
        // Buscar último recibo do mês
        $ultimoRecibo = self::whereYear('created_at', $ano)
            ->whereMonth('created_at', $mes)
            ->whereNotNull('numero_recibo')
            ->orderBy('numero_recibo', 'desc')
            ->first();
        
        if ($ultimoRecibo && preg_match('/REC-(\d{4})(\d{2})-(\d{4})/', $ultimoRecibo->numero_recibo, $matches)) {
            $numero = intval($matches[3]) + 1;
        } else {
            $numero = 1;
        }
        
        return sprintf('REC-%04d%02d-%04d', $ano, $mes, $numero);
    }

    /**
     * Verificar se paciente tem retorno disponível
     */
    public static function temRetornoDisponivel($pacienteId): ?PagamentoConsulta
    {
        return self::where('paciente_id', $pacienteId)
            ->where('permite_retorno', true)
            ->where('data_limite_retorno', '>=', now())
            ->where('status', 'pago')
            ->whereDoesntHave('retornos', function($query) {
                $query->where('status', '!=', 'cancelado');
            })
            ->orderBy('data_pagamento', 'desc')
            ->first();
    }

    /**
     * Marcar pagamento como pago
     */
    public function marcarComoPago(?int $usuarioId = null, ?string $usuarioNome = null): void
    {
        $this->update([
            'status' => 'pago',
            'data_pagamento' => now(),
            'usuario_id' => $usuarioId,
            'usuario_nome' => $usuarioNome,
            'numero_recibo' => $this->numero_recibo ?? self::gerarNumeroRecibo(),
        ]);
    }

    /**
     * Marcar pagamento como isento
     */
    public function marcarComoIsento(string $motivo, ?int $usuarioId = null, ?string $usuarioNome = null): void
    {
        $this->update([
            'status' => 'isento',
            'isencao_aplicada' => true,
            'motivo_isencao' => $motivo,
            'valor_pago' => 0,
            'data_pagamento' => now(),
            'usuario_id' => $usuarioId,
            'usuario_nome' => $usuarioNome,
            'numero_recibo' => $this->numero_recibo ?? self::gerarNumeroRecibo(),
        ]);
    }
    //  */
    // public function marcarComoIsento(?string $motivo, ?int $usuarioId = null, ?string $usuarioNome = null): void
    // {
    //     $motivoFinal = $motivo ?? 'Isenção aplicada';
    //     $this->update([
    //         'status' => 'isento',
    //         'isencao_aplicada' => true,
    //         'motivo_isencao' => $motivoFinal,
    //         'valor_pago' => 0,
    //         'data_pagamento' => now(),
    //         'usuario_id' => $usuarioId,
    //         'usuario_nome' => $usuarioNome,
    //         'numero_recibo' => $this->numero_recibo ?? self::gerarNumeroRecibo(),
    //     ]);
    // }

    /**
     * Cancelar pagamento
     */
    public function cancelar(string $motivo, ?int $usuarioId = null): void
    {
        $this->update([
            'status' => 'cancelado',
            'observacoes' => ($this->observacoes ? $this->observacoes . "\n\n" : '') . 
                            "Cancelado em " . now()->format('d/m/Y H:i') . 
                            " por usuário {$usuarioId}. Motivo: {$motivo}",
        ]);
    }

    /**
     * Aplicar desconto
     */
    public function aplicarDesconto(float $desconto, string $motivo = null): void
    {
        $this->update([
            'desconto' => $desconto,
            'valor_pago' => max(0, $this->valor_original - $desconto),
            'observacoes' => ($this->observacoes ? $this->observacoes . "\n\n" : '') . 
                            "Desconto aplicado: " . number_format($desconto, 2, ',', '.') . " MT" .
                            ($motivo ? ". Motivo: {$motivo}" : ''),
        ]);
    }

    /**
     * Configurar retorno
     */
    public function configurarRetorno(int $diasValidade = 30): void
    {
        $this->update([
            'permite_retorno' => true,
            'dias_validade_retorno' => $diasValidade,
            'data_limite_retorno' => now()->addDays($diasValidade),
        ]);
    }

    /**
     * Accessor para valor formatado
     */
    public function getValorPagoFormatadoAttribute(): string
    {
        return number_format($this->valor_pago, 2, ',', '.') . ' MT';
    }

    public function getValorOriginalFormatadoAttribute(): string
    {
        return number_format($this->valor_original, 2, ',', '.') . ' MT';
    }

    public function getDescontoFormatadoAttribute(): string
    {
        return number_format($this->desconto, 2, ',', '.') . ' MT';
    }

    /**
     * Verificar se está pago ou isento
     */
    public function isPagoOuIsento(): bool
    {
        return in_array($this->status, ['pago', 'isento']);
    }

    /**
     * Verificar se permite retorno
     */
    public function podeUsarRetorno(): bool
    {
        return $this->permite_retorno && 
               $this->data_limite_retorno >= now() &&
               $this->status === 'pago' &&
               !$this->retornos()->where('status', '!=', 'cancelado')->exists();
    }
}
