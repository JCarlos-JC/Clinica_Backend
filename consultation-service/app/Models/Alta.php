<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'altas';

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
        'tipo_alta',
        'data_hora_alta',
        'medico_responsavel_id',
        'medico_responsavel_nome',
        'medico_responsavel_crm',
        'especialidade',
        'data_internacao',
        'tempo_internacao_dias',
        'diagnostico_entrada',
        'diagnostico_final',
        'cid_principal',
        'cids_secundarios',
        'sumario_alta',
        'procedimentos_realizados',
        'cirurgias_realizadas',
        'exames_realizados',
        'evolucao_clinica',
        'condicoes_alta',
        'estado_geral_alta',
        'medicamentos_alta',
        'orientacoes_alta',
        'cuidados_domiciliares',
        'restricoes',
        'dieta_orientada',
        'atividade_fisica_orientada',
        'retorno_consulta',
        'data_retorno',
        'medico_retorno',
        'especialidade_retorno',
        'exames_controle',
        'necessita_acompanhamento',
        'tipo_acompanhamento',
        'frequencia_acompanhamento',
        'destino_paciente',
        'transferido_para',
        'acompanhante_responsavel',
        'parentesco_acompanhante',
        'contato_acompanhante',
        'documentos_entregues',
        'relatorio_alta',
        'numero_relatorio',
        'receitas_entregues',
        'atestado_entregue',
        'guias_exames',
        'orientacoes_impressas',
        'atestado_obito',
        'observacoes',
        'pendencias',
        'finalizada',
        'data_finalizacao',
        'finalizada_por',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_hora_alta' => 'datetime',
        'data_internacao' => 'date',
        'data_retorno' => 'date',
        'data_finalizacao' => 'datetime',
        'cids_secundarios' => 'array',
        'procedimentos_realizados' => 'array',
        'cirurgias_realizadas' => 'array',
        'exames_realizados' => 'array',
        'medicamentos_alta' => 'array',
        'orientacoes_alta' => 'array',
        'cuidados_domiciliares' => 'array',
        'restricoes' => 'array',
        'exames_controle' => 'array',
        'documentos_entregues' => 'array',
        'necessita_acompanhamento' => 'boolean',
        'receitas_entregues' => 'boolean',
        'atestado_entregue' => 'boolean',
        'guias_exames' => 'boolean',
        'orientacoes_impressas' => 'boolean',
        'atestado_obito' => 'boolean',
        'finalizada' => 'boolean',
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
    public function scopeMelhorada($query)
    {
        return $query->where('tipo_alta', 'melhorada');
    }

    public function scopeCurada($query)
    {
        return $query->where('tipo_alta', 'curada');
    }

    public function scopeEvasao($query)
    {
        return $query->where('tipo_alta', 'evasao');
    }

    public function scopeATransferencia($query)
    {
        return $query->where('tipo_alta', 'a_pedido')
            ->orWhere('tipo_alta', 'transferencia');
    }

    public function scopeNecessitamRetorno($query)
    {
        return $query->whereNotNull('data_retorno')
            ->where('data_retorno', '>=', now());
    }

    public function scopePorPaciente($query, $nid)
    {
        return $query->where('nid', $nid);
    }

    public function scopePendentes($query)
    {
        return $query->where('finalizada', false);
    }

    /**
     * Métodos auxiliares
     */
    public function registrar($dados)
    {
        // Calcular tempo de internação
        if ($this->data_internacao && $this->data_hora_alta) {
            $this->tempo_internacao_dias = $this->data_internacao->diffInDays($this->data_hora_alta);
        }

        // Atualizar consulta
        if ($this->consulta) {
            $this->consulta->status = 'alta';
            $this->consulta->data_fim = $this->data_hora_alta;
            $this->consulta->save();
        }

        $this->save();
    }

    public function finalizarDocumentacao($finalizadaPor)
    {
        $this->finalizada = true;
        $this->data_finalizacao = now();
        $this->finalizada_por = $finalizadaPor;
        $this->save();
    }

    public function adicionarPendencia($pendencia)
    {
        $pendencias = $this->pendencias ? json_decode($this->pendencias, true) : [];
        $pendencias[] = [
            'descricao' => $pendencia,
            'data' => now()->toDateTimeString(),
        ];
        $this->pendencias = json_encode($pendencias);
        $this->save();
    }

    public function gerarRelatorioAlta()
    {
        // Gerar número de relatório único
        $this->numero_relatorio = 'ALTA-' . now()->format('YmdHis') . '-' . $this->id;
        
        // Montar relatório completo
        $relatorio = [
            'paciente' => [
                'nid' => $this->nid,
                'nome' => $this->paciente_nome,
            ],
            'internacao' => [
                'data_entrada' => $this->data_internacao,
                'data_saida' => $this->data_hora_alta,
                'tempo_internacao' => $this->tempo_internacao_dias . ' dias',
            ],
            'diagnostico' => [
                'entrada' => $this->diagnostico_entrada,
                'final' => $this->diagnostico_final,
                'cid_principal' => $this->cid_principal,
            ],
            'procedimentos' => $this->procedimentos_realizados,
            'medicamentos' => $this->medicamentos_alta,
            'orientacoes' => $this->orientacoes_alta,
            'retorno' => [
                'data' => $this->data_retorno,
                'medico' => $this->medico_retorno,
                'especialidade' => $this->especialidade_retorno,
            ],
        ];

        $this->relatorio_alta = json_encode($relatorio, JSON_PRETTY_PRINT);
        $this->save();

        return $relatorio;
    }

    /**
     * Atributos computados
     */
    public function getTempoInternacaoFormatadoAttribute()
    {
        if (!$this->tempo_internacao_dias) {
            return 'N/A';
        }
        
        $dias = $this->tempo_internacao_dias;
        if ($dias < 1) {
            return 'Menos de 1 dia';
        }
        return $dias . ' dia' . ($dias > 1 ? 's' : '');
    }

    public function getDocumentacaoCompletaAttribute()
    {
        return $this->receitas_entregues && 
               $this->orientacoes_impressas && 
               !empty($this->relatorio_alta);
    }

    public function getNecessitaCuidadosEspeciaisAttribute()
    {
        return !empty($this->cuidados_domiciliares) || 
               !empty($this->restricoes) || 
               $this->necessita_acompanhamento;
    }
}
