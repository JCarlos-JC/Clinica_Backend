<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especialidade extends Model
{
    use HasFactory;
    
    protected $table = 'especialidades';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'requer_encaminhamento',
        'ativo'
    ];
    
    protected $casts = [
        'requer_encaminhamento' => 'boolean',
        'ativo' => 'boolean',
    ];
}