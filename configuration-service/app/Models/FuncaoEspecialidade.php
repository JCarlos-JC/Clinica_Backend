<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuncaoEspecialidade extends Model
{
    use HasFactory;
    
    protected $table = 'funcao_especialidades';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'pode_prescrever',
        'pode_solicitar_exames',
        'pode_criar_prontuario',
        'ativo'
    ];
    
    protected $casts = [
        'pode_prescrever' => 'boolean',
        'pode_solicitar_exames' => 'boolean',
        'pode_criar_prontuario' => 'boolean',
        'ativo' => 'boolean',
    ];
}