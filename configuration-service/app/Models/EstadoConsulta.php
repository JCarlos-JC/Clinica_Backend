<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoConsulta extends Model
{
    use HasFactory;
    
    protected $table = 'estados_consulta';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'cor',
        'icone',
        'estado_final',
        'requer_encerramento_ciclo',
        'ordem_exibicao',
        'ativo'
    ];
    
    protected $casts = [
        'estado_final' => 'boolean',
        'requer_encerramento_ciclo' => 'boolean',
        'ativo' => 'boolean',
    ];
}