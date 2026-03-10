<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescricao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prescricoes';

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
        'medico_id',
        'medico_nome',
        'medico_crm',
        'medicamento',
        'principio_ativo',
        'dosagem',
        'forma_farmaceutica',
        'via_administracao',
        'frequencia',
        'quantidade_por_dose',
        'unidade_medida',
        'horarios_list',
        'duracao_dias',
        'data_inicio',
        'data_fim',
        'observacoes',
        'orientacoes_uso',
        'efeitos_colaterais',
        'contraindicacoes',
        'uso_continuo',
        'se_necessario',
        'quantidade_total',
        'quantidade_dispensada',
        'data_dispensacao',
        'local_dispensacao',
        'dispensado_por',
        'medicamento_controlado',
        'numero_receita',
        'validade_receita',
        'receituario_especial',
        'cor_receituario',
        'status',
        'prescricao_substituida_id',
        'motivo_substituicao',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'horarios_list' => 'array',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'data_dispensacao' => 'date',
        'validade_receita' => 'date',
        'uso_continuo' => 'boolean',
        'se_necessario' => 'boolean',
        'medicamento_controlado' => 'boolean',
        'receituario_especial' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function consulta()
    {
        return $this->belongsTo(Consulta::class);
    }

    public function prescricaoSubstituida()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_substituida_id');
    }

    public function prescricoesSubstitutas()
    {
        return $this->hasMany(Prescricao::class, 'prescricao_substituida_id');
    }

    /**
     * Scopes
     */
    public function scopeAtivas($query)
    {
        return $query->whereIn('status', ['prescrita', 'dispensada', 'parcialmente_dispensada']);
    }

    public function scopePorPaciente($query, $nid)
    {
        return $query->where('nid', $nid);
    }

    public function scopeControladas($query)
    {
        return $query->where('medicamento_controlado', true);
    }

    public function scopePendentesDispensacao($query)
    {
        return $query->where('status', 'prescrita')
            ->orWhere('status', 'parcialmente_dispensada');
    }

    /**
     * Métodos auxiliares
     */
    public function dispensar($quantidade, $local, $dispensadoPor)
    {
        $this->quantidade_dispensada += $quantidade;
        $this->local_dispensacao = $local;
        $this->dispensado_por = $dispensadoPor;
        $this->data_dispensacao = now();

        if ($this->quantidade_dispensada >= $this->quantidade_total) {
            $this->status = 'dispensada';
        } else {
            $this->status = 'parcialmente_dispensada';
        }

        $this->save();
    }

    public function cancelar($motivo = null)
    {
        $this->status = 'cancelada';
        if ($motivo) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n\n" : '') . "Cancelada: " . $motivo;
        }
        $this->save();
    }

    public function substituir($novaPrescricaoId, $motivo)
    {
        $this->status = 'substituida';
        $this->motivo_substituicao = $motivo;
        $this->save();

        // Atualizar a nova prescrição
        Prescricao::where('id', $novaPrescricaoId)->update([
            'prescricao_substituida_id' => $this->id
        ]);
    }

    /**
     * Atributos computados
     */
    public function getDiasRestantesAttribute()
    {
        if (!$this->data_fim) {
            return null;
        }
        return now()->diffInDays($this->data_fim, false);
    }

    public function getQuantidadeRestanteAttribute()
    {
        return $this->quantidade_total - $this->quantidade_dispensada;
    }

    public function getVencidaAttribute()
    {
        return $this->validade_receita && $this->validade_receita->isPast();
    }
}
