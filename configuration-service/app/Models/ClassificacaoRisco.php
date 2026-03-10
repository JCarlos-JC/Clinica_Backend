<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificacaoRisco extends Model
{
    use HasFactory;
    
    protected $table = 'classificacao_risco';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'cor',
        'tempo_atendimento_minutos',
        'nivel_prioridade',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
}