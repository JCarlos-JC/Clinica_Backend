<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoUtente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoUtenteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tipoUtentes = TipoUtente::when(request('ativo') !== null, function ($query) {
            return $query->where('ativo', request('ativo'));
        })->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $tipoUtentes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:tipo_utentes',
            'codigo' => 'nullable|string|max:50|unique:tipo_utentes',
            'descricao' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $tipoUtente = TipoUtente::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de utente criado com sucesso',
            'data' => $tipoUtente
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tipoUtente = TipoUtente::find($id);

        if (!$tipoUtente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de utente não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tipoUtente
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $tipoUtente = TipoUtente::find($id);

        if (!$tipoUtente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de utente não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100|unique:tipo_utentes,nome,'.$id,
            'codigo' => 'nullable|string|max:50|unique:tipo_utentes,codigo,'.$id,
            'descricao' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $tipoUtente->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de utente atualizado com sucesso',
            'data' => $tipoUtente
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tipoUtente = TipoUtente::find($id);

        if (!$tipoUtente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de utente não encontrado'
            ], 404);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $tipoUtente->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de utente desativado com sucesso'
        ]);
    }

    /**
     * Get consultas disponíveis para um tipo de utente
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConsultasDisponiveis($id)
    {
        try {
            $tipoUtente = TipoUtente::findOrFail($id);
            
            // Buscar todas as consultas disponíveis para este tipo de utente na tabela preco_consultas
            $consultasDisponiveis = $tipoUtente->precos()
                ->with(['tipoConsulta', 'tipoUtente'])
                ->where('ativo', true)
                ->get()
                ->map(function ($preco) {
                    return [
                        'id' => $preco->id,
                        'valor' => $preco->valor,
                        'descricao' => $preco->descricao,
                        'tipo_consulta' => [
                            'id' => $preco->tipoConsulta->id,
                            'nome' => $preco->tipoConsulta->nome,
                            'codigo' => $preco->tipoConsulta->codigo ?? null,
                            'descricao' => $preco->tipoConsulta->descricao ?? null,
                        ],
                        'tipo_utente' => [
                            'id' => $preco->tipoUtente->id,
                            'nome' => $preco->tipoUtente->nome,
                            'codigo' => $preco->tipoUtente->codigo ?? null,
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $consultasDisponiveis
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de utente não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar consultas disponíveis',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}