<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadeOrganica extends Model
{
    use HasFactory;
    
    protected $table = 'unidades_organica';
    
    protected $fillable = [
        'nome',
        'sigla',
        'descricao',
        'tipo',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
}