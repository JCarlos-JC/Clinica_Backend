<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProvinciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $provincias = Provincia::when(request('ativo') !== null, function ($query) {
            return $query->where('ativo', request('ativo'));
        })->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $provincias
        ]);
    }
    
    /**
     * Get active provincias for public access.
     *
     * @return \Illuminate\Http\Response
     */
    public function publicIndex()
    {
        $provincias = Provincia::where('ativo', true)
            ->select('id', 'nome', 'codigo')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $provincias
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
            'nome' => 'required|string|max:100|unique:provincias',
            'codigo' => 'nullable|string|max:20|unique:provincias',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $provincia = Provincia::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Província criada com sucesso',
            'data' => $provincia
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
        $provincia = Provincia::find($id);

        if (!$provincia) {
            return response()->json([
                'status' => 'error',
                'message' => 'Província não encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $provincia
        ]);
    }

    /**
     * Get all districts for a province.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getDistritos($id)
    {
        $provincia = Provincia::find($id);

        if (!$provincia) {
            return response()->json([
                'status' => 'error',
                'message' => 'Província não encontrada'
            ], 404);
        }

        $distritos = $provincia->distritos()
            ->when(request('ativo') !== null, function ($query) {
                return $query->where('ativo', request('ativo'));
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $distritos
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
        $provincia = Provincia::find($id);

        if (!$provincia) {
            return response()->json([
                'status' => 'error',
                'message' => 'Província não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100|unique:provincias,nome,'.$id,
            'codigo' => 'nullable|string|max:20|unique:provincias,codigo,'.$id,
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $provincia->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Província atualizada com sucesso',
            'data' => $provincia
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
        $provincia = Provincia::find($id);

        if (!$provincia) {
            return response()->json([
                'status' => 'error',
                'message' => 'Província não encontrada'
            ], 404);
        }

        // Verificar se existem distritos associados
        if ($provincia->distritos()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível excluir a província pois existem distritos associados'
            ], 422);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $provincia->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Província desativada com sucesso'
        ]);
    }
}