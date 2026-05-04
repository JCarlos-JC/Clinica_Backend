<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViaAdministracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ViaAdministracaoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vias = ViaAdministracao::when(request('ativo') !== null, function ($query) {
            return $query->where('ativo', request('ativo'));
        })->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $vias
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
            'nome' => 'required|string|max:100|unique:vias_administracao',
            'codigo' => 'nullable|string|max:20|unique:vias_administracao',
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

        $via = ViaAdministracao::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Via de administração criada com sucesso',
            'data' => $via
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
        $via = ViaAdministracao::find($id);

        if (!$via) {
            return response()->json([
                'status' => 'error',
                'message' => 'Via de administração não encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $via
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
        $via = ViaAdministracao::find($id);

        if (!$via) {
            return response()->json([
                'status' => 'error',
                'message' => 'Via de administração não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100|unique:vias_administracao,nome,'.$id,
            'codigo' => 'nullable|string|max:20|unique:vias_administracao,codigo,'.$id,
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

        $via->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Via de administração atualizada com sucesso',
            'data' => $via
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
        $via = ViaAdministracao::find($id);

        if (!$via) {
            return response()->json([
                'status' => 'error',
                'message' => 'Via de administração não encontrada'
            ], 404);
        }

        // Verificar se existem medicamentos associados
        if ($via->medicamentos()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível excluir a via de administração pois existem medicamentos associados'
            ], 422);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $via->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Via de administração desativada com sucesso'
        ]);
    }
}