<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoExame extends Model
{
    use HasFactory;
    
    protected $table = 'tipos_exame';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'categoria',
        'preco_padrao',
        'tempo_estimado_minutos',
        'requer_jejum',
        'instrucoes_preparo',
        'instrucoes_coleta',
        'ativo'
    ];
    
    protected $casts = [
        'preco_padrao' => 'decimal:2',
        'requer_jejum' => 'boolean',
        'ativo' => 'boolean',
    ];
}