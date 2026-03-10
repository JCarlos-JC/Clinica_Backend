<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViaAdministracao extends Model
{
    use HasFactory;
    
    protected $table = 'vias_administracao';
    
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
        return $this->hasMany(Medicamento::class, 'via_administracao_id');
    }
}