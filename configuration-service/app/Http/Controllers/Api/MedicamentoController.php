<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Medicamento::with(['forma', 'viaAdministracao']);
        
        if ($request->has('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }
        
        if ($request->has('principio_ativo')) {
            $query->where('principio_ativo', 'like', '%' . $request->principio_ativo . '%');
        }
        
        if ($request->has('forma_id')) {
            $query->where('forma_id', $request->forma_id);
        }
        
        if ($request->has('via_administracao_id')) {
            $query->where('via_administracao_id', $request->via_administracao_id);
        }
        
        if ($request->has('generico')) {
            $query->where('generico', $request->generico);
        }
        
        if ($request->has('controlado')) {
            $query->where('controlado', $request->controlado);
        }
        
        if ($request->has('ativo')) {
            $query->where('ativo', $request->ativo);
        }
        
        $medicamentos = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $medicamentos
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
            'principio_ativo' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50|unique:medicamentos',
            'forma_id' => 'required|exists:formas_medicamento,id',
            'via_administracao_id' => 'required|exists:vias_administracao,id',
            'dosagem' => 'required|string|max:50',
            'unidade_dosagem' => 'required|string|max:20',
            'instrucoes_padrao' => 'nullable|string',
            'contraindicacoes' => 'nullable|string',
            'efeitos_colaterais' => 'nullable|string',
            'controlado' => 'nullable|boolean',
            'generico' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verificar unicidade composta
        $existente = Medicamento::where('nome', $request->nome)
            ->where('principio_ativo', $request->principio_ativo)
            ->where('forma_id', $request->forma_id)
            ->where('dosagem', $request->dosagem)
            ->first();
            
        if ($existente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já existe um medicamento com estas características'
            ], 422);
        }

        $medicamento = Medicamento::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Medicamento criado com sucesso',
            'data' => $medicamento
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
        $medicamento = Medicamento::with(['forma', 'viaAdministracao'])->find($id);

        if (!$medicamento) {
            return response()->json([
                'status' => 'error',
                'message' => 'Medicamento não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $medicamento
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
        $medicamento = Medicamento::find($id);

        if (!$medicamento) {
            return response()->json([
                'status' => 'error',
                'message' => 'Medicamento não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100',
            'principio_ativo' => 'sometimes|required|string|max:255',
            'codigo' => 'nullable|string|max:50|unique:medicamentos,codigo,'.$id,
            'forma_id' => 'sometimes|required|exists:formas_medicamento,id',
            'via_administracao_id' => 'sometimes|required|exists:vias_administracao,id',
            'dosagem' => 'sometimes|required|string|max:50',
            'unidade_dosagem' => 'sometimes|required|string|max:20',
            'instrucoes_padrao' => 'nullable|string',
            'contraindicacoes' => 'nullable|string',
            'efeitos_colaterais' => 'nullable|string',
            'controlado' => 'nullable|boolean',
            'generico' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Verificar unicidade composta se houver alterações nos campos relevantes
        if ($request->has('nome') || $request->has('principio_ativo') || $request->has('forma_id') || $request->has('dosagem')) {
            $nome = $request->has('nome') ? $request->nome : $medicamento->nome;
            $principio_ativo = $request->has('principio_ativo') ? $request->principio_ativo : $medicamento->principio_ativo;
            $forma_id = $request->has('forma_id') ? $request->forma_id : $medicamento->forma_id;
            $dosagem = $request->has('dosagem') ? $request->dosagem : $medicamento->dosagem;
            
            $existente = Medicamento::where('nome', $nome)
                ->where('principio_ativo', $principio_ativo)
                ->where('forma_id', $forma_id)
                ->where('dosagem', $dosagem)
                ->where('id', '!=', $id)
                ->first();
                
            if ($existente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um medicamento com estas características'
                ], 422);
            }
        }

        $medicamento->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Medicamento atualizado com sucesso',
            'data' => $medicamento
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
        $medicamento = Medicamento::find($id);

        if (!$medicamento) {
            return response()->json([
                'status' => 'error',
                'message' => 'Medicamento não encontrado'
            ], 404);
        }

        // Alternar para inativo em vez de excluir fisicamente
        $medicamento->update(['ativo' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Medicamento desativado com sucesso'
        ]);
    }
}