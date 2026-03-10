<?php
// filepath: services/triage-service/app/Models/Triagem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Triagem extends Model
{
    use HasFactory;

    protected $table = 'triagens';
    
    protected $fillable = [
        'codigo_triagem',
        'paciente_id',
        'triagem_id',
        'nid',
        'nome',
        'apelido',
        'genero',
        'data_nascimento',
        'enfermeiro_id',
        'enfermeiro_nome',
        'data_hora_inicio',
        'data_hora_fim',
        'data_cadastro',
        'data_triagem',
        'estado_urgencia',
        'tipo_utente',
        'tipo_triagem',
        'status',
        'consulta_id',
        'consulta_agendada',
        'observacoes',
    ];
    
    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'data_cadastro' => 'datetime',
        'data_triagem' => 'datetime',
        'data_nascimento' => 'date',
        'consulta_agendada' => 'boolean',
    ];
    
    protected $appends = [
        'duracao_minutos',
        'idade',
        'tempo_espera'
    ];
    
    /**
     * Relationship with Sinais Vitais
     */
    public function sinaisVitais()
    {
        return $this->hasOne(SinaisVitais::class);
    }
    
    /**
     * Relationship with Agendamento Consulta
     */
    public function agendamentoConsulta()
    {
        return $this->hasOne(AgendamentoConsulta::class);
    }
    
    /**
     * Get duration of triage in minutes
     */
    public function getDuracaoMinutosAttribute()
    {
        if (!$this->data_hora_inicio) return 0;
        
        if (!$this->data_hora_fim) {
            return Carbon::parse($this->data_hora_inicio)->diffInMinutes(now());
        }
        
        return Carbon::parse($this->data_hora_inicio)->diffInMinutes($this->data_hora_fim);
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
     * Get waiting time in minutes
     */
    public function getTempoEsperaAttribute()
    {
        if (!$this->data_cadastro) return 0;
        
        $dataFim = $this->data_triagem ?? now();
        return Carbon::parse($this->data_cadastro)->diffInMinutes($dataFim);
    }
    
    /**
     * Generate unique triage code
     */
    public static function gerarCodigoTriagem()
    {
        $ano = date('Y');
        $ultimaTriagem = self::whereYear('created_at', $ano)
                             ->orderBy('id', 'desc')
                             ->first();
                             
        $sequencia = $ultimaTriagem ? intval(substr($ultimaTriagem->codigo_triagem, -6)) + 1 : 1;
        
        return "TRI{$ano}" . str_pad($sequencia, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Scope for pending triages (aguardando triagem)
     * Matches: pacientesTriagemPendente from TriagemPaciente.jsx
     */
    public function scopeAguardandoTriagem($query)
    {
        return $query->where('status', 'aguardando_triagem')
                     ->where(function($q) {
                         $q->whereNull('tipo_triagem')
                           ->orWhere('tipo_triagem', 'inicial');
                     })
                     ->orderByRaw("
                         CASE estado_urgencia
                             WHEN 'emergencia' THEN 1
                             WHEN 'urgente' THEN 2
                             WHEN 'normal' THEN 3
                             ELSE 4
                         END
                     ")
                     ->orderBy('data_cadastro', 'asc');
    }
    
    /**
     * Scope for completed triages (triagem concluída)
     * Matches: triagensConcluidasLista from TriagemPaciente.jsx
     */
    public function scopeTriagemConcluida($query)
    {
        return $query->where('status', 'triagem_concluida')
                     ->where(function($q) {
                         $q->whereNull('tipo_triagem')
                           ->orWhere('tipo_triagem', 'inicial');
                     })
                     ->orderBy('data_triagem', 'desc');
    }
    
    /**
     * Scope for triages waiting for consultation scheduling
     */
    public function scopeAguardandoAgendamento($query)
    {
        return $query->where('status', 'triagem_concluida')
                     ->where('consulta_agendada', false)
                     ->orderBy('data_triagem', 'asc');
    }
    
    /**
     * Scope by urgency level
     */
    public function scopePorUrgencia($query, $urgencia)
    {
        return $query->where('estado_urgencia', $urgencia);
    }
    
    /**
     * Scope by triage type
     */
    public function scopePorTipoTriagem($query, $tipo)
    {
        return $query->where('tipo_triagem', $tipo);
    }
    
    /**
     * Scope for initial triages only
     */
    public function scopeTriagemInicial($query)
    {
        return $query->where(function($q) {
            $q->whereNull('tipo_triagem')
              ->orWhere('tipo_triagem', 'inicial');
        });
    }
    
    /**
     * Search scope for NID, Nome, Apelido
     * Matches: searchPendentes and searchConcluidas filters
     */
    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;
        
        return $query->where(function($q) use ($search) {
            $q->where('nid', 'like', "%{$search}%")
              ->orWhere('nome', 'like', "%{$search}%")
              ->orWhere('apelido', 'like', "%{$search}%");
        });
    }
    
    /**
     * Mark triage as started (em triagem)
     */
    public function iniciarTriagem($enfermeiroId = null, $enfermeiroNome = null)
    {
        $this->update([
            'status' => 'em_triagem',
            'data_hora_inicio' => now(),
            'enfermeiro_id' => $enfermeiroId ?? $this->enfermeiro_id,
            'enfermeiro_nome' => $enfermeiroNome ?? $this->enfermeiro_nome,
        ]);
        
        return $this;
    }
    
    /**
     * Mark triage as completed (triagem concluída)
     */
    public function concluirTriagem()
    {
        $this->update([
            'status' => 'triagem_concluida',
            'data_hora_fim' => now(),
            'data_triagem' => now(),
        ]);
        
        return $this;
    }
    
    /**
     * Mark consultation as scheduled
     */
    public function marcarConsultaAgendada($consultaId)
    {
        $this->update([
            'consulta_id' => $consultaId,
            'consulta_agendada' => true,
        ]);
        
        return $this;
    }
    
    /**
     * Cancel triage
     */
    public function cancelar($motivo = null)
    {
        $this->update([
            'status' => 'cancelada',
            'data_hora_fim' => now(),
            'observacoes' => $this->observacoes ? $this->observacoes . "\nCancelado: " . $motivo : "Cancelado: " . $motivo,
        ]);
        
        return $this;
    }
    
    /**
     * Check if has scheduled consultation
     */
    public function hasConsultaAgendada()
    {
        return $this->agendamentoConsulta()->exists();
    }
    
    /**
     * Get statistics for dashboard
     */
    public static function getEstatisticas($dataInicio = null, $dataFim = null)
    {
        $query = self::query();
        
        if ($dataInicio) {
            $query->whereDate('created_at', '>=', $dataInicio);
        }
        
        if ($dataFim) {
            $query->whereDate('created_at', '<=', $dataFim);
        }
        
        return [
            'total' => $query->count(),
            'aguardando_triagem' => (clone $query)->where('status', 'aguardando_triagem')->count(),
            'em_triagem' => (clone $query)->where('status', 'em_triagem')->count(),
            'triagem_concluida' => (clone $query)->where('status', 'triagem_concluida')->count(),
            'consultas_agendadas' => (clone $query)->where('consulta_agendada', true)->count(),
            'por_urgencia' => [
                'emergencia' => (clone $query)->where('estado_urgencia', 'emergencia')->count(),
                'urgente' => (clone $query)->where('estado_urgencia', 'urgente')->count(),
                'normal' => (clone $query)->where('estado_urgencia', 'normal')->count(),
            ],
            'tempo_medio_espera' => (clone $query)
                ->where('status', 'triagem_concluida')
                ->get()
                ->avg(function($triagem) {
                    return $triagem->tempo_espera;
                }),
        ];
    }
}