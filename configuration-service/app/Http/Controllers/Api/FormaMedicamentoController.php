<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormaMedicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormaMedicamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $formas = FormaMedicamento::when(request('ativo') !== null, function ($query) {
            return $query->where('ativo', request('ativo'));
        })->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $formas
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
            'nome' => 'required|string|max:100|unique:formas_medicamento',
            'codigo' => 'nullable|string|max:20|unique:formas_medicamento',
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

        $forma = FormaMedicamento::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Forma de medicamento criada com sucesso',
            'data' => $forma
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
        $forma = FormaMedicamento::find($id);

        if (!$forma) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forma de medicamento não encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $forma
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
        $forma = FormaMedicamento::find($id);

        if (!$forma) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forma de medicamento não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100|unique:formas_medicamento,nome,'.$id,
            'codigo' => 'nullable|string|max:20|unique:formas_medicamento,codigo,'.$id,
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

        $forma->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Forma de medicamento atualizada com sucesso',
            'data' => $forma
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
        $forma = FormaMedicamento::find($id);

        if (!$forma) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forma de medicamento não encontrada'
            ], 404);
        }

        // Verificar se existem medicamentos associados
        if ($forma->medicamentos()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível excluir a forma pois existem medicamentos associados'
            ], 422);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $forma->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Forma de medicamento desativada com sucesso'
        ]);
    }
}