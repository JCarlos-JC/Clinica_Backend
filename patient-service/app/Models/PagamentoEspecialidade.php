<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagamentoEspecialidade extends Model
{
    protected $table = 'pagamento_especialidades';

    protected $fillable = [
        'paciente_id',
        'consulta_id',
        'agendamento_id',
        'nid',
        'especialidade_destino',
        'medico_destino_id',
        'valor_consulta',
        'metodo_pagamento_id',
        'observacoes',
        'status_pagamento',
        'data_pagamento'
    ];

    protected $casts = [
        'valor_consulta' => 'decimal:2',
        'data_pagamento' => 'datetime'
    ];

    /**
     * Relacionamento com Paciente
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Scopes
     */
    public function scopeConfirmados($query)
    {
        return $query->where('status_pagamento', 'confirmado');
    }

    public function scopePorPaciente($query, $pacienteId)
    {
        return $query->where('paciente_id', $pacienteId);
    }

    public function scopePorConsulta($query, $consultaId)
    {
        return $query->where('consulta_id', $consultaId);
    }
}
