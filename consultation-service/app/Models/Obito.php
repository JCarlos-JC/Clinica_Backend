<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obito extends Model
{
    use HasFactory;

    protected $table = 'obitos';

    // Óbitos não usam soft deletes por questões legais

    // Ocultar campos técnicos/internos do frontend
    protected $hidden = [
        'created_by',
        'updated_by',
    ];

    protected $fillable = [
        'consulta_id',
        'nid',
        'paciente_id',
        'paciente_nome',
        'data_nascimento',
        'idade',
        'sexo',
        'data_hora_obito',
        'local_obito',
        'tipo_obito',
        'medico_atestante_id',
        'medico_atestante_nome',
        'medico_atestante_crm',
        'causa_imediata',
        'causa_intermediaria',
        'causa_basica',
        'tempo_doenca',
        'outras_condicoes',
        'morte_violenta',
        'tipo_violencia',
        'acidente_trabalho',
        'fonte_informacao',
        'circunstancias_obito',
        'necropsia',
        'numero_declaracao_obito',
        'data_emissao_do',
        'cartorio',
        'numero_registro',
        'livro',
        'folha',
        'sepultamento_cremacao',
        'destino_corpo',
        'cemiterio_crematario',
        'endereco_sepultamento',
        'data_sepultamento',
        'numero_autorizacao',
        'responsavel_corpo',
        'parentesco_responsavel',
        'cpf_responsavel',
        'contato_responsavel',
        'funeraria',
        'contato_funeraria',
        'numero_protocolo_funeraria',
        'observacoes',
        'documentos_anexos',
        'declaracao_emitida',
        'corpo_liberado',
        'data_liberacao_corpo',
        'liberado_por',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_hora_obito' => 'datetime',
        'data_emissao_do' => 'date',
        'data_sepultamento' => 'date',
        'data_liberacao_corpo' => 'datetime',
        'morte_violenta' => 'boolean',
        'acidente_trabalho' => 'boolean',
        'necropsia' => 'boolean',
        'declaracao_emitida' => 'boolean',
        'corpo_liberado' => 'boolean',
        'outras_condicoes' => 'array',
        'documentos_anexos' => 'array',
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
    public function scopeNatural($query)
    {
        return $query->where('tipo_obito', 'natural');
    }

    public function scopeViolento($query)
    {
        return $query->where('tipo_obito', 'violento')
            ->orWhere('morte_violenta', true);
    }

    public function scopeMalDefinido($query)
    {
        return $query->where('tipo_obito', 'mal_definido');
    }

    public function scopeAguardandoDeclaracao($query)
    {
        return $query->where('declaracao_emitida', false);
    }

    public function scopeAguardandoLiberacao($query)
    {
        return $query->where('corpo_liberado', false)
            ->where('declaracao_emitida', true);
    }

    public function scopePorPaciente($query, $nid)
    {
        return $query->where('nid', $nid);
    }

    public function scopeComNecropsia($query)
    {
        return $query->where('necropsia', true);
    }

    /**
     * Métodos auxiliares
     */
    public function registrarDeclaracao($numeroDeclaracao, $emitidaPor)
    {
        $this->numero_declaracao_obito = $numeroDeclaracao;
        $this->data_emissao_do = now();
        $this->declaracao_emitida = true;
        $this->save();

        // Atualizar consulta
        if ($this->consulta) {
            $this->consulta->status = 'obito';
            $this->consulta->data_fim = $this->data_hora_obito;
            $this->consulta->save();
        }
    }

    public function liberarCorpo($liberadoPor)
    {
        if (!$this->declaracao_emitida) {
            throw new \Exception('Declaração de óbito deve ser emitida antes de liberar o corpo');
        }

        $this->corpo_liberado = true;
        $this->data_liberacao_corpo = now();
        $this->liberado_por = $liberadoPor;
        $this->save();
    }

    public function registrarSepultamento($dados)
    {
        $this->sepultamento_cremacao = $dados['tipo'] ?? 'sepultamento';
        $this->cemiterio_crematario = $dados['local'] ?? null;
        $this->endereco_sepultamento = $dados['endereco'] ?? null;
        $this->data_sepultamento = $dados['data'] ?? now();
        $this->numero_autorizacao = $dados['numero_autorizacao'] ?? null;
        $this->save();
    }

    public function vincularFuneraria($funeraria, $contato, $protocolo = null)
    {
        $this->funeraria = $funeraria;
        $this->contato_funeraria = $contato;
        $this->numero_protocolo_funeraria = $protocolo ?? 'FUN-' . now()->format('YmdHis');
        $this->save();
    }

    public function registrarCartorio($dados)
    {
        $this->cartorio = $dados['nome'];
        $this->numero_registro = $dados['numero_registro'] ?? null;
        $this->livro = $dados['livro'] ?? null;
        $this->folha = $dados['folha'] ?? null;
        $this->save();
    }

    /**
     * Atributos computados
     */
    public function getIdadeObitoAttribute()
    {
        if (!$this->data_nascimento || !$this->data_hora_obito) {
            return $this->idade ?? 'N/A';
        }
        
        return $this->data_nascimento->diffInYears($this->data_hora_obito);
    }

    public function getTempoDesdeObitoAttribute()
    {
        if (!$this->data_hora_obito) {
            return null;
        }

        $diff = $this->data_hora_obito->diff(now());
        
        if ($diff->days > 0) {
            return $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        }
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    }

    public function getRequerInvestigacaoAttribute()
    {
        return $this->morte_violenta || 
               $this->tipo_obito === 'violento' || 
               $this->tipo_obito === 'mal_definido' ||
               $this->acidente_trabalho;
    }

    public function getDocumentacaoCompletaAttribute()
    {
        return $this->declaracao_emitida && 
               !empty($this->numero_declaracao_obito) &&
               !empty($this->cartorio);
    }

    /**
     * Validações
     */
    public static function validarCausaMorte($causaImediata, $causaBasica)
    {
        if (empty($causaImediata) || empty($causaBasica)) {
            throw new \Exception('Causa imediata e causa básica são obrigatórias');
        }

        if ($causaImediata === $causaBasica) {
            throw new \Exception('Causa imediata não pode ser igual à causa básica');
        }

        return true;
    }

    public function validarLiberacaoCorpo()
    {
        if (!$this->declaracao_emitida) {
            throw new \Exception('Declaração de óbito não foi emitida');
        }

        if ($this->requer_investigacao && !$this->necropsia) {
            throw new \Exception('Óbito requer investigação. Necropsia pendente');
        }

        if ($this->corpo_liberado) {
            throw new \Exception('Corpo já foi liberado');
        }

        return true;
    }
}
