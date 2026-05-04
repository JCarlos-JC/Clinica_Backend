<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distrito;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DistritoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Distrito::with('provincia');
        
        if ($request->has('provincia_id')) {
            $query->where('provincia_id', $request->provincia_id);
        }
        
        if ($request->has('ativo')) {
            $query->where('ativo', $request->ativo);
        }
        
        $distritos = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $distritos
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
            'provincia_id' => 'required|exists:provincias,id',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verificar unicidade composta (nome + provincia_id)
        $existente = Distrito::where('nome', $request->nome)
            ->where('provincia_id', $request->provincia_id)
            ->first();
            
        if ($existente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já existe um distrito com este nome nesta província'
            ], 422);
        }

        $distrito = Distrito::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Distrito criado com sucesso',
            'data' => $distrito
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
        $distrito = Distrito::with('provincia')->find($id);

        if (!$distrito) {
            return response()->json([
                'status' => 'error',
                'message' => 'Distrito não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $distrito
        ]);
    }
    
    /**
     * Get all bairros for a distrito.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getBairros($id)
    {
        $distrito = Distrito::find($id);

        if (!$distrito) {
            return response()->json([
                'status' => 'error',
                'message' => 'Distrito não encontrado'
            ], 404);
        }

        $bairros = $distrito->bairros()
            ->when(request('ativo') !== null, function ($query) {
                return $query->where('ativo', request('ativo'));
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bairros
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
        $distrito = Distrito::find($id);

        if (!$distrito) {
            return response()->json([
                'status' => 'error',
                'message' => 'Distrito não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100',
            'codigo' => 'nullable|string|max:20',
            'provincia_id' => 'sometimes|required|exists:provincias,id',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verificar unicidade composta (nome + provincia_id) apenas se algum desses campos mudar
        if ($request->has('nome') || $request->has('provincia_id')) {
            $nome = $request->has('nome') ? $request->nome : $distrito->nome;
            $provinciaId = $request->has('provincia_id') ? $request->provincia_id : $distrito->provincia_id;
            
            $existente = Distrito::where('nome', $nome)
                ->where('provincia_id', $provinciaId)
                ->where('id', '!=', $id)
                ->first();
                
            if ($existente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um distrito com este nome nesta província'
                ], 422);
            }
        }

        $distrito->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Distrito atualizado com sucesso',
            'data' => $distrito
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
        $distrito = Distrito::find($id);

        if (!$distrito) {
            return response()->json([
                'status' => 'error',
                'message' => 'Distrito não encontrado'
            ], 404);
        }

        // Verificar se existem bairros associados
        if ($distrito->bairros()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível excluir o distrito pois existem bairros associados'
            ], 422);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $distrito->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Distrito desativado com sucesso'
        ]);
    }
}