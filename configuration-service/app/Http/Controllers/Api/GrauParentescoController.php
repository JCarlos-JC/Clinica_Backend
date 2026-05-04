<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GrauParentesco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrauParentescoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $grauParentescos = GrauParentesco::when(request('ativo') !== null, function ($query) {
            return $query->where('ativo', request('ativo'));
        })->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $grauParentescos
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
            'nome' => 'required|string|max:100|unique:grau_parentesco',
            'codigo' => 'nullable|string|max:20|unique:grau_parentesco',
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

        $grauParentesco = GrauParentesco::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Grau de parentesco criado com sucesso',
            'data' => $grauParentesco
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
        $grauParentesco = GrauParentesco::find($id);

        if (!$grauParentesco) {
            return response()->json([
                'status' => 'error',
                'message' => 'Grau de parentesco não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $grauParentesco
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
        $grauParentesco = GrauParentesco::find($id);

        if (!$grauParentesco) {
            return response()->json([
                'status' => 'error',
                'message' => 'Grau de parentesco não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100|unique:grau_parentesco,nome,'.$id,
            'codigo' => 'nullable|string|max:20|unique:grau_parentesco,codigo,'.$id,
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

        $grauParentesco->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Grau de parentesco atualizado com sucesso',
            'data' => $grauParentesco
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
        $grauParentesco = GrauParentesco::find($id);

        if (!$grauParentesco) {
            return response()->json([
                'status' => 'error',
                'message' => 'Grau de parentesco não encontrado'
            ], 404);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $grauParentesco->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Grau de parentesco desativado com sucesso'
        ]);
    }
}