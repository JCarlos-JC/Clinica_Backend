<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoUrgencia extends Model
{
    use HasFactory;
    
    protected $table = 'estados_urgencia';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'cor',
        'icone',
        'nivel_prioridade',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
}