<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transferencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transferencias';

    // Ocultar campos técnicos/internos do frontend
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    protected $fillable = [
        'consulta_id',
        'nid',
        'paciente_id',
        'paciente_nome',
        'tipo_transferencia',
        'medico_origem_id',
        'medico_origem_nome',
        'especialidade_origem',
        'setor_origem',
        'hospital_origem',
        'medico_destino_id',
        'medico_destino_nome',
        'especialidade_destino',
        'setor_destino',
        'hospital_destino',
        'endereco_destino',
        'contato_destino',
        'motivo_transferencia',
        'sumario_clinico',
        'diagnostico_principal',
        'diagnosticos_secundarios',
        'procedimentos_realizados',
        'medicamentos_em_uso',
        'exames_realizados',
        'estado_geral',
        'sinais_vitais',
        'restricoes_alimentares',
        'alergias',
        'necessita_isolamento',
        'tipo_isolamento',
        'urgencia',
        'data_hora_solicitacao',
        'data_hora_prevista',
        'data_hora_efetivada',
        'tipo_transporte',
        'observacoes_transporte',
        'necessita_oxigenio',
        'necessita_monitor',
        'necessita_bomba_infusao',
        'acompanhante_nome',
        'acompanhante_parentesco',
        'acompanhante_contato',
        'documentos_transferencia',
        'numero_protocolo',
        'status',
        'motivo_recusa',
        'motivo_cancelamento',
        'data_aceite',
        'aceita_por',
        'data_recusa',
        'recusada_por',
        'valor_transporte',
        'responsavel_pagamento',
        'observacoes',
        'intercorrencias',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'medicamentos_em_uso' => 'array',
        'exames_realizados' => 'array',
        'sinais_vitais' => 'array',
        'documentos_transferencia' => 'array',
        'data_hora_solicitacao' => 'datetime',
        'data_hora_prevista' => 'datetime',
        'data_hora_efetivada' => 'datetime',
        'data_aceite' => 'datetime',
        'data_recusa' => 'datetime',
        'necessita_isolamento' => 'boolean',
        'necessita_oxigenio' => 'boolean',
        'necessita_monitor' => 'boolean',
        'necessita_bomba_infusao' => 'boolean',
        'valor_transporte' => 'decimal:2',
    ];

    /**
     * Relacionamentos
     */
    public function consulta()
    {
        return $this->belongsTo(Consulta::class);
    }

    /**
     * Scopes
     */
    public function scopeSolicitadas($query)
    {
        return $query->where('status', 'solicitada');
    }

    public function scopeAguardandoVaga($query)
    {
        return $query->where('status', 'aguardando_vaga');
    }

    public function scopeAguardandoTransporte($query)
    {
        return $query->where('status', 'aguardando_transporte');
    }

    public function scopeEmTransito($query)
    {
        return $query->where('status', 'em_transito');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('urgencia', 'urgente')
            ->orWhere('urgencia', 'emergencia');
    }

    public function scopePorPaciente($query, $nid)
    {
        return $query->where('nid', $nid);
    }

    public function scopePorMedicoOrigem($query, $medicoId)
    {
        return $query->where('medico_origem_id', $medicoId);
    }

    public function scopePorMedicoDestino($query, $medicoId)
    {
        return $query->where('medico_destino_id', $medicoId);
    }

    /**
     * Métodos auxiliares
     */
    public function aceitar($aceitaPor)
    {
        $this->status = 'aceita';
        $this->data_aceite = now();
        $this->aceita_por = $aceitaPor;
        $this->save();

        // Atualizar status da consulta
        if ($this->consulta) {
            $this->consulta->status = 'transferido_' . ($this->tipo_transferencia === 'entre_especialidades' ? 'especialidade' : 'medico');
            $this->consulta->save();
        }
    }

    public function recusar($motivo, $recusadaPor)
    {
        $this->status = 'recusada';
        $this->motivo_recusa = $motivo;
        $this->data_recusa = now();
        $this->recusada_por = $recusadaPor;
        $this->save();
    }

    public function iniciarTransporte()
    {
        $this->status = 'em_transito';
        $this->save();
    }

    public function concluir($intercorrencias = null)
    {
        $this->status = 'concluida';
        $this->data_hora_efetivada = now();
        if ($intercorrencias) {
            $this->intercorrencias = $intercorrencias;
        }
        $this->save();

        // Atualizar consulta
        if ($this->consulta && $this->tipo_transferencia === 'entre_medicos') {
            $this->consulta->medico_id = $this->medico_destino_id;
            $this->consulta->medico = $this->medico_destino_nome;
            $this->consulta->transferido = true;
            $this->consulta->medico_anterior = $this->medico_origem_nome;
            $this->consulta->motivo_transferencia = $this->motivo_transferencia;
            $this->consulta->save();
        }
    }

    public function cancelar($motivo)
    {
        $this->status = 'cancelada';
        $this->motivo_cancelamento = $motivo;
        $this->save();
    }

    /**
     * Atributos computados
     */
    public function getTempoEsperaAttribute()
    {
        if ($this->data_hora_efetivada) {
            return $this->data_hora_solicitacao->diffInMinutes($this->data_hora_efetivada);
        }
        return $this->data_hora_solicitacao->diffInMinutes(now());
    }

    public function getNecessitaRecursosEspeciaisAttribute()
    {
        return $this->necessita_oxigenio || $this->necessita_monitor || $this->necessita_bomba_infusao;
    }

    public function getEstadoCriticoAttribute()
    {
        return in_array($this->estado_geral, ['grave', 'critico']);
    }
}
