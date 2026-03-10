<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    use HasFactory;
    
    protected $table = 'distritos';
    
    protected $fillable = [
        'nome',
        'codigo',
        'provincia_id',
        'ativo'
    ];
    
    protected $casts = [
        'ativo' => 'boolean',
    ];
    
    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }
    
    public function bairros()
    {
        return $this->hasMany(Bairro::class);
    }
}