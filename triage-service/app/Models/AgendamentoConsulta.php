<?php
// filepath: services/triage-service/app/Models/AgendamentoConsulta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AgendamentoConsulta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agendamentos_consulta';
    
    protected $fillable = [
        'codigo_agendamento',
        'triagem_id',
        'paciente_id',
        'nid',
        'nome',
        'apelido',
        'genero',
        'data_nascimento',
        'especialidade',
        'especialidade_id',
        'medico',
        'medico_id',
        'tipo_consulta',
        'tipo_consulta_id',
        'data_consulta',
        'hora_consulta',
        'motivo_consulta',
        'observacoes',
        'data_agendamento',
        'data_confirmacao',
        'data_cancelamento',
        'status',
        'tipo',
        'status_pagamento',
        'consulta_id',
        'enviado_consultation_service',
        'data_envio_consultation_service',
        'motivo_cancelamento',
        'cancelado_por',
        'prioridade',
    ];
    
    protected $casts = [
        'data_nascimento' => 'date',
        'data_consulta' => 'date',
        'data_agendamento' => 'datetime',
        'data_confirmacao' => 'datetime',
        'data_cancelamento' => 'datetime',
        'data_envio_consultation_service' => 'datetime',
        'enviado_consultation_service' => 'boolean',
    ];
    
    protected $appends = [
        'idade',
        'data_hora_consulta',
        'dias_ate_consulta',
        'pode_cancelar',
        'valido',
        'requer_pagamento'
    ];
    
    /**
     * Relationship with Triagem
     */
    public function triagem()
    {
        return $this->belongsTo(Triagem::class);
    }
    
    /**
     * Get patient age
     */
    public function getIdadeAttribute()
    {
        if (!$this->data_nascimento) return null;
        
        return Carbon::parse($this->data_nascimento)->age;
    }
    
    /**
     * Get full datetime of consultation
     */
    public function getDataHoraConsultaAttribute()
    {
        if (!$this->data_consulta || !$this->hora_consulta) return null;
        
        return Carbon::parse($this->data_consulta->format('Y-m-d') . ' ' . $this->hora_consulta);
    }
    
    /**
     * Get days until consultation
     */
    public function getDiasAteConsultaAttribute()
    {
        if (!$this->data_consulta) return null;
        
        $hoje = Carbon::today();
        $dataConsulta = Carbon::parse($this->data_consulta);
        
        return $hoje->diffInDays($dataConsulta, false);
    }
    
    /**
     * Check if consultation can be cancelled
     */
    public function getPodeCancelarAttribute()
    {
        return in_array($this->status, ['agendado', 'confirmada']);
    }
    
    /**
     * Check if agendamento is valid based on tipo and status_pagamento
     */
    public function getValidoAttribute()
    {
        return $this->isValido();
    }
    
    /**
     * Check if agendamento requires payment
     */
    public function getRequerPagamentoAttribute()
    {
        return $this->tipo === 'transferencia_especialidade';
    }
    
    /**
     * Generate unique scheduling code
     */
    public static function gerarCodigoAgendamento()
    {
        $ano = date('Y');
        $mes = date('m');
        
        // Buscar pelo código_agendamento (não pelo ID) para evitar problemas com tentativas falhas
        $ultimoAgendamento = self::where('codigo_agendamento', 'like', "AGD{$ano}{$mes}%")
                                  ->orderBy('codigo_agendamento', 'desc')
                                  ->first();
                                  
        $sequencia = $ultimoAgendamento ? intval(substr($ultimoAgendamento->codigo_agendamento, -6)) + 1 : 1;
        
        // Garantir que o código seja único (retry até 10 vezes se necessário)
        $tentativas = 0;
        do {
            $codigo = "AGD{$ano}{$mes}" . str_pad($sequencia, 6, '0', STR_PAD_LEFT);
            $existe = self::where('codigo_agendamento', $codigo)->exists();
            
            if ($existe) {
                $sequencia++;
                $tentativas++;
            }
        } while ($existe && $tentativas < 10);
        
        return $codigo;
    }
    
    /**
     * Scope for scheduled consultations (agendado)
     */
    public function scopeAguardandoConsulta($query)
    {
        return $query->where('status', 'agendado')
                     ->orderByRaw("
                         CASE tipo_consulta
                             WHEN 'Emergência' THEN 1
                             WHEN 'Muito Urgente' THEN 2
                             WHEN 'Urgente' THEN 3
                             WHEN 'Não Urgência' THEN 4
                             ELSE 5
                         END
                     ")
                     ->orderBy('data_consulta', 'asc')
                     ->orderBy('hora_consulta', 'asc');
    }
    
    /**
     * Scope for today's consultations
     */
    public function scopeConsultasHoje($query)
    {
        return $query->whereDate('data_consulta', today())
                     ->whereIn('status', ['agendado', 'confirmada', 'em_atendimento'])
                     ->orderBy('hora_consulta', 'asc');
    }
    
    /**
     * Scope for valid agendamentos (respects tipo and status_pagamento)
     * Transferências de especialidade precisam ter status_pagamento = 'pago'
     */
    public function scopeAgendamentosValidos($query)
    {
        return $query->where(function ($q) {
            // Triagem e transferência de médico: não precisa verificar pagamento
            $q->whereIn('tipo', ['triagem', 'transferencia_medico'])
              // OU transferência de especialidade com pagamento confirmado
              ->orWhere(function ($q2) {
                  $q2->where('tipo', 'transferencia_especialidade')
                     ->where('status_pagamento', 'pago');
              });
        });
    }
    
    /**
     * Check if agendamento is valid based on tipo and status_pagamento
     */
    public function isValido()
    {
        // Transferência de especialidade requer pagamento confirmado
        if ($this->tipo === 'transferencia_especialidade') {
            return $this->status_pagamento === 'pago';
        }
        
        // Outros tipos são válidos sem verificar pagamento
        return true;
    }
    
    /**
     * Get consultation data for consultation service
     * Matches: handleAgendarConsulta data structure from TriagemPaciente.jsx
     */
    public function getConsultationServiceData()
    {
        // Buscar dados atualizados do médico do authentication-service
        $medicoData = null;
        if ($this->medico_id) {
            try {
                $authService = app(\App\Services\AuthServiceClient::class);
                $medicoData = $authService->getMedico($this->medico_id);
            } catch (\Exception $e) {
                Log::warning('Não foi possível buscar dados do médico do auth-service', [
                    'medico_id' => $this->medico_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Usar dados do auth-service se disponível, caso contrário usar dados locais
        $medicoNome = $medicoData['nome'] ?? $this->medico;
        $medicoId = $medicoData['id'] ?? $this->medico_id;
        
        return [
            'agendamento_id' => $this->id,
            'codigo_agendamento' => $this->codigo_agendamento,
            'triagem_id' => $this->triagem_id,
            'paciente_id' => $this->paciente_id,
            'nid' => $this->nid,
            'nome' => $this->nome,
            'apelido' => $this->apelido,
            'genero' => $this->genero,
            'data_nascimento' => $this->data_nascimento?->format('Y-m-d'),
            'idade' => $this->idade,
            'especialidade' => $this->especialidade,
            'especialidade_id' => $this->especialidade_id,
            'medico' => $medicoNome,  // Dados do authentication-service
            'medico_id' => $medicoId,  // Dados do authentication-service
            'medico_email' => $medicoData['email'] ?? null,  // Adicional do auth-service
            'medico_cargo' => $medicoData['cargo'] ?? null,  // Adicional do auth-service
            'tipo_consulta' => $this->tipo_consulta,
            'tipo_consulta_id' => $this->tipo_consulta_id,
            'data_consulta' => $this->data_consulta->format('Y-m-d'),
            'hora_consulta' => $this->hora_consulta,
            'motivo_consulta' => $this->motivo_consulta,
            'observacoes' => $this->observacoes,
            'prioridade' => $this->prioridade,
            'status' => $this->status,
            'data_agendamento' => $this->data_agendamento->toIso8601String(),
            // Include vital signs from triage
            'sinais_vitais' => $this->triagem && $this->triagem->sinaisVitais 
                ? $this->triagem->sinaisVitais->getSummary() 
                : null,
        ];
    }
    
    /**
     * Confirm consultation
     */
    public function confirmar()
    {
        $this->update([
            'status' => 'confirmada',
            'data_confirmacao' => now(),
        ]);
        
        return $this;
    }
    
    /**
     * Cancel consultation
     */
    public function cancelar($motivo, $canceladoPor = null)
    {
        $this->update([
            'status' => 'cancelada',
            'data_cancelamento' => now(),
            'motivo_cancelamento' => $motivo,
            'cancelado_por' => $canceladoPor,
        ]);
        
        return $this;
    }
    
    /**
     * Mark as sent to consultation service
     */
    public function marcarEnviado($consultaId)
    {
        $this->update([
            'enviado_consultation_service' => true,
            'data_envio_consultation_service' => now(),
            'consulta_id' => $consultaId,
        ]);
        
        return $this;
    }
}