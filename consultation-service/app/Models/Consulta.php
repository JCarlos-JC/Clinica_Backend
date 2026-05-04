<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Consulta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultas';

    protected $fillable = [
        'agendamento_id',
        'nid',
        'paciente_id',
        'triagem_id',
        'medico',
        'medico_id',
        'tipo_consulta',
        'tipo_consulta_id',
        'especialidade',
        'especialidade_id',
        'data_consulta',
        'hora_consulta',
        'data_hora_inicio',
        'data_hora_fim',
        'motivo_consulta',
        'observacoes',
        'anamnese',
        'exame_fisico',
        'hipotese_diagnostica',
        'prescricao',
        'procedimentos',
        'exames_solicitados',
        'plano_tratamento',
        'orientacoes',
        'data_retorno',
        'status',
        'prioridade',
        'valor_consulta',
        'status_pagamento',
        'forma_pagamento',
        'data_pagamento',
        'transferido',
        'medico_anterior',
        'motivo_transferencia',
        'encaminhamento',
        'atestado_medico',
        'dias_atestado',
        'documentos_anexos',
        'sincronizado_triagem',
        'data_sincronizacao_triagem',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_consulta' => 'date',
        'hora_consulta' => 'datetime:H:i',
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'data_retorno' => 'date',
        'data_pagamento' => 'datetime',
        'valor_consulta' => 'decimal:2',
        'transferido' => 'boolean',
        'sincronizado_triagem' => 'boolean',
        'data_sincronizacao_triagem' => 'datetime',
        'documentos_anexos' => 'array',
    ];

    // Ocultar campos técnicos/internos do frontend
    protected $hidden = [
        'sincronizado_triagem',
        'data_sincronizacao_triagem',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    /**
     * Relacionamentos
     */
    public function anexos()
    {
        return $this->hasMany(ConsultaAnexo::class, 'consulta_id');
    }

    public function historico()
    {
        return $this->hasMany(HistoricoConsulta::class, 'consulta_id');
    }

    public function prescricoes()
    {
        return $this->hasMany(Prescricao::class, 'consulta_id');
    }

    public function exames()
    {
        return $this->hasMany(Exame::class, 'consulta_id');
    }

    public function transferencias()
    {
        return $this->hasMany(Transferencia::class, 'consulta_id');
    }

    public function transferenciaHistorico()
    {
        return $this->hasMany(TransferenciaHistorico::class, 'consulta_id');
    }

    public function alta()
    {
        return $this->hasOne(Alta::class, 'consulta_id');
    }

    public function obito()
    {
        return $this->hasOne(Obito::class, 'consulta_id');
    }

    /**
     * Scopes
     */
    public function scopeAgendadas($query)
    {
        return $query->where('status', 'agendada');
    }

    public function scopeEmAtendimento($query)
    {
        return $query->where('status', 'em_atendimento');
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('status', 'finalizada');
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_consulta', today());
    }

    public function scopePorMedico($query, $medicoId)
    {
        return $query->where('medico_id', $medicoId);
    }

    public function scopePorPaciente($query, $pacienteId)
    {
        return $query->where('paciente_id', $pacienteId);
    }

    public function scopePorNid($query, $nid)
    {
        return $query->where('nid', $nid);
    }

    /**
     * Métodos auxiliares
     */
    public function iniciarAtendimento()
    {
        $this->status = 'em_atendimento';
        $this->data_hora_inicio = now();
        $this->save();

        $this->registrarHistorico('consulta_iniciada', 'agendada', 'em_atendimento', 
            'Consulta iniciada às ' . now()->format('H:i'));
    }

    public function finalizarAtendimento()
    {
        $this->status = 'finalizada';
        $this->data_hora_fim = now();
        $this->save();

        $this->registrarHistorico('consulta_finalizada', 'em_atendimento', 'finalizada', 
            'Consulta finalizada às ' . now()->format('H:i'));
    }

    public function cancelar($motivo = null)
    {
        $statusAnterior = $this->status;
        $this->status = 'cancelada';
        $this->save();

        $this->registrarHistorico('consulta_cancelada', $statusAnterior, 'cancelada', 
            'Consulta cancelada. Motivo: ' . ($motivo ?? 'Não informado'));
    }

    public function transferirMedico($novoMedico, $novoMedicoId, $motivo)
    {
        $medicoAnterior = $this->medico;
        
        $this->medico_anterior = $medicoAnterior;
        $this->medico = $novoMedico;
        $this->medico_id = $novoMedicoId;
        $this->motivo_transferencia = $motivo;
        $this->transferido = true;
        $this->save();

        $this->registrarHistorico('medico_transferido', null, null, 
            "Transferido de {$medicoAnterior} para {$novoMedico}. Motivo: {$motivo}");
    }

    public function transferirEspecialidade($especialidadeDestino, $especialidadeDestinoId, $medicoDestino, $medicoDestinoId, $motivo, $observacoes = null)
    {
        $especialidadeAnterior = $this->especialidade;
        $medicoAnterior = $this->medico;
        $medicoAnteriorId = $this->medico_id;
        
        $this->medico_anterior = $medicoAnterior;
        $this->especialidade = $especialidadeDestino;
        $this->especialidade_id = $especialidadeDestinoId;
        $this->medico = $medicoDestino;
        $this->medico_id = $medicoDestinoId;
        $this->motivo_transferencia = $motivo;
        $this->transferido = true;
        $this->status = 'transferido_especialidade';
        
        if ($observacoes) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n\n" : '') . 
                "[Transferência] " . $observacoes;
        }
        
        $this->save();

        // Registrar na tabela de histórico de transferências
        TransferenciaHistorico::create([
            'consulta_id' => $this->id,
            'tipo' => 'especialidade',
            'medico_origem_id' => $medicoAnteriorId,
            'medico_destino_id' => $medicoDestinoId,
            'especialidade_origem' => $especialidadeAnterior,
            'especialidade_destino' => $especialidadeDestino,
            'motivo' => $motivo,
            'observacoes' => $observacoes,
            'data_transferencia' => now(),
        ]);

        $detalhes = "Transferido de {$especialidadeAnterior} ({$medicoAnterior}) para {$especialidadeDestino} ({$medicoDestino}). Motivo: {$motivo}";
        if ($observacoes) {
            $detalhes .= "\nObservações: {$observacoes}";
        }

        $this->registrarHistorico('especialidade_transferida', null, 'transferido_especialidade', $detalhes);
    }

    public function registrarHistorico($acao, $statusAnterior = null, $statusNovo = null, $detalhes = null)
    {
        // Registrar no histórico local
        $historico = $this->historico()->create([
            'acao' => $acao,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'detalhes' => $detalhes,
            'usuario_id' => Auth::check() ? Auth::id() : null,
        ]);

        // Enviar para o patient-service usando NID
        if ($this->nid) {
            try {
                $patientService = app(\App\Services\PatientServiceClient::class);
                $patientService->registrarHistoricoConsulta($this->nid, [
                    'consulta_id' => $this->id,
                    'data_consulta' => $this->data_consulta ? $this->data_consulta->format('Y-m-d') : null,
                    'medico' => $this->medico,
                    'tipo_consulta' => $this->tipo_consulta,
                    'acao' => $acao,
                    'status' => $this->status,
                    'detalhes' => $detalhes,
                    'anamnese' => $this->anamnese,
                    'diagnostico' => $this->hipotese_diagnostica,
                    'prescricao' => $this->prescricao,
                    'exames_solicitados' => $this->exames_solicitados,
                    'data_registro' => now()->toDateTimeString()
                ], request()->bearerToken());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Falha ao enviar histórico para patient-service', [
                    'nid' => $this->nid,
                    'consulta_id' => $this->id,
                    'erro' => $e->getMessage()
                ]);
            }
        }

        return $historico;
    }

    /**
     * Helper methods para operações comuns
     */
    public function prescreverMedicamento($dados)
    {
        return $this->prescricoes()->create($dados);
    }

    public function solicitarExame($dados)
    {
        return $this->exames()->create($dados);
    }

    public function transferir($dados)
    {
        $this->transferido = true;
        $this->medico_anterior = $this->medico;
        $this->motivo_transferencia = $dados['motivo_transferencia'] ?? null;
        $this->save();

        return $this->transferencias()->create($dados);
    }

    public function darAlta($dados)
    {
        $this->status = 'alta';
        $this->data_fim = now();
        $this->save();

        $this->registrarHistorico('alta_medica', $this->getOriginal('status'), 'alta', 
            'Alta médica registrada');

        return $this->alta()->create($dados);
    }

    public function registrarObito($dados)
    {
        $this->status = 'obito';
        $this->data_fim = $dados['data_hora_obito'] ?? now();
        $this->save();

        $this->registrarHistorico('obito', $this->getOriginal('status'), 'obito', 
            'Óbito registrado');

        return $this->obito()->create($dados);
    }

    /**
     * Atributos computados
     */
    public function getDuracaoConsultaAttribute()
    {
        if ($this->data_hora_inicio && $this->data_hora_fim) {
            return $this->data_hora_inicio->diffInMinutes($this->data_hora_fim);
        }
        return null;
    }

    public function getDataHoraConsultaAttribute()
    {
        return $this->data_consulta->format('Y-m-d') . ' ' . $this->hora_consulta;
    }
}

