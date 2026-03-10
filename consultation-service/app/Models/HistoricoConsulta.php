<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoConsulta extends Model
{
    use HasFactory;

    protected $table = 'historico_consultas';

    // Ocultar campos técnicos/internos do frontend
    protected $hidden = [
        'usuario_id', // Já tem usuario_nome
    ];

    protected $fillable = [
        'consulta_id',
        'acao',
        'status_anterior',
        'status_novo',
        'detalhes',
        'usuario_id',
        'usuario_nome',
    ];

    public function consulta()
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }
}
