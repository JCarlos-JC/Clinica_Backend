<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrauParentesco extends Model
{
    use HasFactory;
    
    protected $table = 'grau_parentesco';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
}