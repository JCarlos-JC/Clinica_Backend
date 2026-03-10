<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exame extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exames';

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
        'medico_solicitante_id',
        'medico_solicitante_nome',
        'tipo_exame',
        'nome_exame',
        'codigo_exame',
        'descricao',
        'indicacao_clinica',
        'data_solicitacao',
        'urgencia',
        'observacoes_solicitacao',
        'informacoes_clinicas',
        'data_agendamento',
        'hora_agendamento',
        'local_realizacao',
        'setor',
        'data_hora_coleta',
        'coletado_por',
        'material_biologico',
        'preparo_necessario',
        'jejum_necessario',
        'horas_jejum',
        'data_hora_analise',
        'analisado_por',
        'equipamento_utilizado',
        'metodo_analise',
        'resultados',
        'valores_referencia',
        'interpretacao',
        'observacoes_resultado',
        'resultado_qualitativo',
        'data_hora_laudo',
        'laudado_por',
        'laudado_por_nome',
        'laudado_por_crm',
        'laudo_medico',
        'conclusao',
        'recomendacoes',
        'arquivos_anexos',
        'caminho_resultado_pdf',
        'caminho_imagem',
        'notificar_medico',
        'notificar_paciente',
        'data_notificacao_medico',
        'data_notificacao_paciente',
        'status',
        'valor_exame',
        'status_pagamento',
        'motivo_cancelamento',
        'data_cancelamento',
        'cancelado_por',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_solicitacao' => 'date',
        'data_agendamento' => 'date',
        'data_hora_coleta' => 'datetime',
        'data_hora_analise' => 'datetime',
        'data_hora_laudo' => 'datetime',
        'data_notificacao_medico' => 'datetime',
        'data_notificacao_paciente' => 'datetime',
        'data_cancelamento' => 'datetime',
        'resultados' => 'array',
        'valores_referencia' => 'array',
        'arquivos_anexos' => 'array',
        'jejum_necessario' => 'boolean',
        'notificar_medico' => 'boolean',
        'notificar_paciente' => 'boolean',
        'valor_exame' => 'decimal:2',
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
    public function scopeSolicitados($query)
    {
        return $query->where('status', 'solicitado');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('urgencia', 'urgente')
            ->orWhere('urgencia', 'emergencia');
    }

    public function scopePendentesLaudo($query)
    {
        return $query->whereIn('status', ['coletado', 'em_analise']);
    }

    public function scopeDisponiveisParaVisualizacao($query)
    {
        return $query->whereIn('status', ['laudado', 'disponivel']);
    }

    public function scopePorPaciente($query, $nid)
    {
        return $query->where('nid', $nid);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_exame', $tipo);
    }

    /**
     * Métodos auxiliares
     */
    public function agendar($data, $hora, $local)
    {
        $this->data_agendamento = $data;
        $this->hora_agendamento = $hora;
        $this->local_realizacao = $local;
        $this->status = 'agendado';
        $this->save();
    }

    public function registrarColeta($coletadoPor, $materialBiologico = null)
    {
        $this->data_hora_coleta = now();
        $this->coletado_por = $coletadoPor;
        if ($materialBiologico) {
            $this->material_biologico = $materialBiologico;
        }
        $this->status = 'coletado';
        $this->save();
    }

    public function iniciarAnalise($analisadoPor, $equipamento = null, $metodo = null)
    {
        $this->data_hora_analise = now();
        $this->analisado_por = $analisadoPor;
        $this->equipamento_utilizado = $equipamento;
        $this->metodo_analise = $metodo;
        $this->status = 'em_analise';
        $this->save();
    }

    public function registrarResultado($resultados, $valoresReferencia = null, $resultadoQualitativo = null)
    {
        $this->resultados = $resultados;
        $this->valores_referencia = $valoresReferencia;
        $this->resultado_qualitativo = $resultadoQualitativo;
        $this->save();
    }

    public function registrarLaudo($laudadoPor, $laudadoPorNome, $laudadoPorCrm, $laudoMedico, $conclusao = null)
    {
        $this->data_hora_laudo = now();
        $this->laudado_por = $laudadoPor;
        $this->laudado_por_nome = $laudadoPorNome;
        $this->laudado_por_crm = $laudadoPorCrm;
        $this->laudo_medico = $laudoMedico;
        $this->conclusao = $conclusao;
        $this->status = 'laudado';
        $this->save();

        // Notificar médico solicitante
        if ($this->notificar_medico) {
            $this->enviarNotificacaoMedico();
        }
    }

    public function disponibilizar()
    {
        $this->status = 'disponivel';
        $this->save();

        // Notificar paciente se configurado
        if ($this->notificar_paciente) {
            $this->enviarNotificacaoPaciente();
        }
    }

    public function marcarComoVisualizado()
    {
        $this->status = 'visualizado';
        $this->save();
    }

    public function cancelar($motivo, $canceladoPor)
    {
        $this->status = 'cancelado';
        $this->motivo_cancelamento = $motivo;
        $this->data_cancelamento = now();
        $this->cancelado_por = $canceladoPor;
        $this->save();
    }

    protected function enviarNotificacaoMedico()
    {
        $this->data_notificacao_medico = now();
        $this->save();
        // TODO: Implementar envio de notificação (email, push, etc)
    }

    protected function enviarNotificacaoPaciente()
    {
        $this->data_notificacao_paciente = now();
        $this->save();
        // TODO: Implementar envio de notificação (email, push, etc)
    }

    /**
     * Atributos computados
     */
    public function getTempoEsperaAttribute()
    {
        if ($this->status === 'laudado' && $this->data_solicitacao) {
            return $this->data_solicitacao->diffInDays($this->data_hora_laudo);
        }
        return null;
    }

    public function getResultadoAlteradoAttribute()
    {
        return in_array($this->resultado_qualitativo, ['alterado', 'critico']);
    }

    public function getResultadoCriticoAttribute()
    {
        return $this->resultado_qualitativo === 'critico';
    }
}
