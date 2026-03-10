<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPagamento extends Model
{
    use HasFactory;
    
    protected $table = 'metodos_pagamento';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'requer_comprovante',
        'requer_confirmacao',
        'ativo'
    ];
    
    protected $casts = [
        'requer_comprovante' => 'boolean',
        'requer_confirmacao' => 'boolean',
        'ativo' => 'boolean',
    ];
}