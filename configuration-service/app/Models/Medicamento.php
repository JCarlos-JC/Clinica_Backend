<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    use HasFactory;
    
    protected $table = 'medicamentos';
    
    protected $fillable = [
        'nome',
        'principio_ativo',
        'codigo',
        'forma_id',
        'via_administracao_id',
        'dosagem',
        'unidade_dosagem',
        'instrucoes_padrao',
        'contraindicacoes',
        'efeitos_colaterais',
        'controlado',
        'generico',
        'ativo'
    ];
    
    protected $casts = [
        'controlado' => 'boolean',
        'generico' => 'boolean',
        'ativo' => 'boolean',
    ];
    
    public function forma()
    {
        return $this->belongsTo(FormaMedicamento::class, 'forma_id');
    }
    
    public function viaAdministracao()
    {
        return $this->belongsTo(ViaAdministracao::class, 'via_administracao_id');
    }
}