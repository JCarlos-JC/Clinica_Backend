<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bairro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BairroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Bairro::with('distrito.provincia');
        
        if ($request->has('distrito_id')) {
            $query->where('distrito_id', $request->distrito_id);
        }
        
        if ($request->has('ativo')) {
            $query->where('ativo', $request->ativo);
        }
        
        $bairros = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $bairros
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
            'nome' => 'required|string|max:100',
            'codigo' => 'nullable|string|max:20',
            'distrito_id' => 'required|exists:distritos,id',
            'codigo_postal' => 'nullable|string|max:20',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verificar unicidade composta (nome + distrito_id)
        $existente = Bairro::where('nome', $request->nome)
            ->where('distrito_id', $request->distrito_id)
            ->first();
            
        if ($existente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já existe um bairro com este nome neste distrito'
            ], 422);
        }

        $bairro = Bairro::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bairro criado com sucesso',
            'data' => $bairro
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
        $bairro = Bairro::with('distrito.provincia')->find($id);

        if (!$bairro) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bairro não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $bairro
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
        $bairro = Bairro::find($id);

        if (!$bairro) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bairro não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100',
            'codigo' => 'nullable|string|max:20',
            'distrito_id' => 'sometimes|required|exists:distritos,id',
            'codigo_postal' => 'nullable|string|max:20',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verificar unicidade composta (nome + distrito_id) apenas se algum desses campos mudar
        if ($request->has('nome') || $request->has('distrito_id')) {
            $nome = $request->has('nome') ? $request->nome : $bairro->nome;
            $distritoId = $request->has('distrito_id') ? $request->distrito_id : $bairro->distrito_id;
            
            $existente = Bairro::where('nome', $nome)
                ->where('distrito_id', $distritoId)
                ->where('id', '!=', $id)
                ->first();
                
            if ($existente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um bairro com este nome neste distrito'
                ], 422);
            }
        }

        $bairro->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bairro atualizado com sucesso',
            'data' => $bairro
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
        $bairro = Bairro::find($id);

        if (!$bairro) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bairro não encontrado'
            ], 404);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $bairro->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Bairro desativado com sucesso'
        ]);
    }
}