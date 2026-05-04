<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaMedicamento extends Model
{
    use HasFactory;
    
    protected $table = 'formas_medicamento';
    
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
    
    public function medicamentos()
    {
        return $this->hasMany(Medicamento::class, 'forma_id');
    }
}