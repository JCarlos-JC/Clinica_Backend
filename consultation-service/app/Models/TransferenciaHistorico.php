<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferenciaHistorico extends Model
{
    protected $table = 'transferencia_historico';
    
    protected $fillable = [
        'consulta_id',
        'tipo',
        'medico_origem_id',
        'medico_destino_id',
        'especialidade_origem',
        'especialidade_destino',
        'motivo',
        'observacoes',
        'data_transferencia'
    ];

    protected $casts = [
        'data_transferencia' => 'datetime'
    ];

    public function consulta()
    {
        return $this->belongsTo(Consulta::class);
    }

    public function medicoOrigem()
    {
        return $this->belongsTo(User::class, 'medico_origem_id');
    }

    public function medicoDestino()
    {
        return $this->belongsTo(User::class, 'medico_destino_id');
    }
}
