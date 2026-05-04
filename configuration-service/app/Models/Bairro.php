<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bairro extends Model
{
    use HasFactory;
    
    protected $table = 'bairros';
    
    protected $fillable = [
        'nome',
        'codigo',
        'distrito_id',
        'codigo_postal',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
    
    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
}