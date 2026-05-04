<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tipoDocumentos = TipoDocumento::when(request('ativo') !== null, function ($query) {
            return $query->where('ativo', request('ativo'));
        })->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $tipoDocumentos
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
            'nome' => 'required|string|max:100|unique:tipo_documentos',
            'codigo' => 'nullable|string|max:20|unique:tipo_documentos',
            'descricao' => 'nullable|string',
            'formato_validacao' => 'nullable|string|max:255',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $tipoDocumento = TipoDocumento::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de documento criado com sucesso',
            'data' => $tipoDocumento
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
        $tipoDocumento = TipoDocumento::find($id);

        if (!$tipoDocumento) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de documento não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tipoDocumento
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
        $tipoDocumento = TipoDocumento::find($id);

        if (!$tipoDocumento) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de documento não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100|unique:tipo_documentos,nome,'.$id,
            'codigo' => 'nullable|string|max:20|unique:tipo_documentos,codigo,'.$id,
            'descricao' => 'nullable|string',
            'formato_validacao' => 'nullable|string|max:255',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $tipoDocumento->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de documento atualizado com sucesso',
            'data' => $tipoDocumento
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
        $tipoDocumento = TipoDocumento::find($id);

        if (!$tipoDocumento) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de documento não encontrado'
            ], 404);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $tipoDocumento->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de documento desativado com sucesso'
        ]);
    }
}