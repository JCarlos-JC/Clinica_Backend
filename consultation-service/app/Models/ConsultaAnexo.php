<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultaAnexo extends Model
{
    use HasFactory;

    protected $table = 'consulta_anexos';

    // Ocultar campos técnicos/internos do frontend
    protected $hidden = [
        'caminho_arquivo', // Ocultar caminho completo por segurança
    ];

    protected $fillable = [
        'consulta_id',
        'tipo',
        'nome_arquivo',
        'caminho_arquivo',
        'mime_type',
        'tamanho',
        'descricao',
    ];

    public function consulta()
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }
}
