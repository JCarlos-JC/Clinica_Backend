<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pacientes';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Paciente $paciente) {
            if (empty($paciente->nid)) {
                $paciente->nid = static::gerarNID();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Identificação
        'nid',
        
        // Informações Pessoais
        'nome',
        'apelido',
        'data_nascimento',
        'genero',
        'estado_civil',
        
        // Raça e Nacionalidade
        'raca_id',
        'nacionalidade',
        
        // Tipo de Utente
        'tipo_utente_id',
        'unidade_organica_id',
        
        // Informações de Familiar
        'nome_familiar',
        'unidade_organica_familiar',
        
        // Informações de Contato
        'celular',
        'celular_alternativo',
        'email',
        'whatsapp',
        
        // Endereço
        'provincia_id',
        'distrito_id',
        'bairro_id',
        'avenida_rua_celula',
        'numero_casa',
        'quarteirao',
        
        // Documento
        'tipo_documento_id',
        'bilhete_identidade',
        'documento',
        'documento_path',
        
        // Status do Paciente
        'status',
        
        // Informações de Transferência para Especialidade
        'hospital_proveniencia',
        'especialidade_anterior',
        'especialidade',
        'medico',
        'data_transferencia',
        'motivo',
        
        // Informações de Pagamento
        'status_pagamento',
        'tipo_consulta_id',
        'metodo_pagamento_id',
        'data_pagamento',
        
        // Informações de Acompanhamento
        'tem_acompanhamento_disponivel',
        'data_limite_acompanhamento',
        'ultimo_ciclo_terminado',
        
        'observacoes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_nascimento' => 'date',
        'data_transferencia' => 'datetime',
        'data_pagamento' => 'datetime',
        'tem_acompanhamento_disponivel' => 'boolean',
        'data_limite_acompanhamento' => 'datetime',
        'ultimo_ciclo_terminado' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be appended to model arrays.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'idade',
        'nome_completo',
    ];

    // ==================== ACCESSORS ====================

    /**
     * Get the patient's full name.
     */
    public function getNomeCompletoAttribute(): string
    {
        return "{$this->nome} {$this->apelido}";
    }

    /**
     * Get the patient's age.
     */
    public function getIdadeAttribute(): ?int
    {
        if (!$this->data_nascimento) {
            return null;
        }
        
        return $this->data_nascimento->diffInYears(now());
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active patients.
     */
    public function scopeAtivo($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope a query to only include inactive patients.
     */
    public function scopeInativo($query)
    {
        return $query->where('status', 'inativo');
    }

    /**
     * Scope a query to filter by gender.
     */
    public function scopeGenero($query, $genero)
    {
        return $query->where('genero', $genero);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search by name or last name.
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
     * Get the patient's relatives.
     */
    public function parentes()
    {
        return $this->hasMany(Parente::class, 'paciente_nid', 'nid');
    }

    /**
     * Get the patient's autonomous user record.
     */
    public function utenteAutonomo()
    {
        return $this->hasOne(UtenteAutonomo::class, 'paciente_id');
    }

    /**
     * Get the patient's medical history.
     */
    // public function historicoPaciente()
    // {
    //     return $this->hasMany(HistoricoPaciente::class, 'paciente_id');
    // }

    /**
     * Get the patient's exam requests.
     */
    public function solicitacoesExames()
    {
        return $this->hasMany(SolicitacaoExame::class, 'paciente_id');
    }

    /**
     * Get the patient's triage records.
     */
    public function solicitacoesTriagem()
    {
        return $this->hasMany(SolicitacaoTriagem::class, 'paciente_id');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if patient is active.
     */
    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Check if patient has pending payment.
     */
    public function hasPendingPayment(): bool
    {
        return $this->status_pagamento === 'pendente';
    }

    /**
     * Check if patient has follow-up available.
     */
    public function hasAcompanhamentoDisponivel(): bool
    {
        return $this->tem_acompanhamento_disponivel === true;
    }

    /**
     * Get patient's age in years.
     */
    public function getAge(): ?int
    {
        return $this->idade;
    }

    /**
     * Activate the patient.
     */
    public function ativar(): bool
    {
        $this->status = 'ativo';
        return $this->save();
    }

    /**
     * Deactivate the patient.
     */
    public function desativar(): bool
    {
        $this->status = 'inativo';
        return $this->save();
    }

    /**
     * Mark patient as discharged (alta).
     */
    public function darAlta(): bool
    {
        $this->status = 'alta';
        return $this->save();
    }

    /**
     * Mark patient as deceased (óbito).
     */
    public function registrarObito(): bool
    {
        $this->status = 'obito';
        return $this->save();
    }

    // ==================== NID GENERATION ====================

    /**
     * Generate a unique NID for the patient.
     * Format: 0000/ANO (e.g., 0001/2025)
     */
    public static function gerarNID(): string
    {
        $ano = now()->year;
        
        // Buscar o último NID do ano atual
        $ultimoPaciente = static::whereYear('created_at', $ano)
            ->whereNotNull('nid')
            ->where('nid', 'like', "%/{$ano}")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($ultimoPaciente && $ultimoPaciente->nid) {
            // Extrair o número sequencial do NID (ex: "0001/2025" -> 1)
            $partes = explode('/', $ultimoPaciente->nid);
            $numeroAtual = (int) $partes[0];
            $proximoNumero = $numeroAtual + 1;
        } else {
            // Primeiro paciente do ano
            $proximoNumero = 1;
        }
        
        // Formatar com 4 dígitos: 0001, 0002, etc.
        $numeroFormatado = str_pad($proximoNumero, 4, '0', STR_PAD_LEFT);
        
        return "{$numeroFormatado}/{$ano}";
    }

    /**
     * Get the next available NID for the current year.
     */
    public static function proximoNID(): string
    {
        return static::gerarNID();
    }
    public function raca()
    {
        return $this->belongsTo(Raca::class, 'raca_id');
    }
    public function tipoUtente()
    {
        return $this->belongsTo(TipoUtente::class, 'tipo_utente_id');
    }
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }
    public function unidadeOrganica()
    {
        return $this->belongsTo(UnidadeOrganica::class, 'unidade_organica_id');
    }
    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }
    public function distrito()
    {
        return $this->belongsTo(Distrito::class, 'distrito_id');
    }
    public function bairro()
    {
        return $this->belongsTo(Bairro::class, 'bairro_id');
    }
    
    /**
     * Relacionamento com pagamentos de consultas
     * 
     * NOVO: Pagamentos agora são armazenados em tabela separada
     * permitindo histórico completo e múltiplas consultas
     */
    public function pagamentosConsultas()
    {
        return $this->hasMany(PagamentoConsulta::class, 'paciente_id');
    }
    
    public function ultimoPagamento()
    {
        return $this->hasOne(PagamentoConsulta::class, 'paciente_id')
            ->latestOfMany('data_pagamento');
    }
    
    public function pagamentosAtivos()
    {
        return $this->hasMany(PagamentoConsulta::class, 'paciente_id')
            ->whereIn('status', ['pago', 'isento']);
    }
    
    /**
     * DEPRECATED: Manter por compatibilidade temporária
     * TODO: Remover após migração completa
     */
    public function tipoConsulta()
    {
        return $this->belongsTo(TipoConsulta::class, 'tipo_consulta_id');
    }
    public function metodoPagamento()
    {
        return $this->belongsTo(MetodoPagamento::class, 'metodo_pagamento_id');
    }
}