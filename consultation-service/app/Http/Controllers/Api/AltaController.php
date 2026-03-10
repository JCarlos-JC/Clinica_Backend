<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alta;
use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AltaController extends Controller
{
    /**
     * Listar todas as altas
     */
    public function index(Request $request)
    {
        $query = Alta::with('consulta');

        // Filtros
        if ($request->has('nid')) {
            $query->porPaciente($request->nid);
        }

        if ($request->has('tipo_alta')) {
            $query->where('tipo_alta', $request->tipo_alta);
        }

        if ($request->has('melhorada')) {
            $query->melhorada();
        }

        if ($request->has('curada')) {
            $query->curada();
        }

        if ($request->has('necessitam_retorno')) {
            $query->necessitamRetorno();
        }

        if ($request->has('pendentes')) {
            $query->pendentes();
        }

        $altas = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($altas);
    }

    /**
     * Exibir uma alta específica
     */
    public function show($id)
    {
        $alta = Alta::with('consulta')->findOrFail($id);
        return response()->json($alta);
    }

    /**
     * Criar nova alta
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consulta_id' => 'required|exists:consultas,id',
            'nid' => 'required|string',
            'tipo_alta' => 'required|string',
            'diagnostico_final' => 'required|string',
            'cid_principal' => 'required|string|max:10',
            'sumario_alta' => 'required|string',
            'condicoes_alta' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $alta = Alta::create($request->all());
        $alta->registrar($request->all());

        return response()->json([
            'message' => 'Alta registrada com sucesso',
            'data' => $alta
        ], 201);
    }

    /**
     * Atualizar alta
     */
    public function update(Request $request, $id)
    {
        $alta = Alta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tipo_alta' => 'sometimes|string',
            'diagnostico_final' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $alta->update($request->all());

        return response()->json([
            'message' => 'Alta atualizada com sucesso',
            'data' => $alta
        ]);
    }

    /**
     * Deletar alta
     */
    public function destroy($id)
    {
        $alta = Alta::findOrFail($id);
        $alta->delete();

        return response()->json([
            'message' => 'Alta removida com sucesso'
        ]);
    }

    /**
     * Finalizar documentação da alta
     */
    public function finalizarDocumentacao(Request $request, $id)
    {
        $alta = Alta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'finalizada_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $alta->finalizarDocumentacao($request->finalizada_por);

            return response()->json([
                'message' => 'Documentação finalizada com sucesso',
                'data' => $alta->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao finalizar documentação',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Adicionar pendência
     */
    public function adicionarPendencia(Request $request, $id)
    {
        $alta = Alta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'pendencia' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $alta->adicionarPendencia($request->pendencia);

            return response()->json([
                'message' => 'Pendência adicionada com sucesso',
                'data' => $alta->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar pendência',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Gerar relatório de alta
     */
    public function gerarRelatorio($id)
    {
        $alta = Alta::findOrFail($id);

        try {
            $relatorio = $alta->gerarRelatorioAlta();

            return response()->json([
                'message' => 'Relatório gerado com sucesso',
                'data' => $relatorio
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao gerar relatório',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Listar alta de uma consulta
     */
    public function porConsulta($consultaId)
    {
        $consulta = Consulta::findOrFail($consultaId);
        $alta = $consulta->alta;

        if (!$alta) {
            return response()->json([
                'message' => 'Nenhuma alta encontrada para esta consulta'
            ], 404);
        }

        return response()->json($alta);
    }

    /**
     * Listar altas melhoradas
     */
    public function melhoradas()
    {
        $altas = Alta::melhorada()
            ->with('consulta')
            ->orderBy('data_hora_alta', 'desc')
            ->get();

        return response()->json($altas);
    }

    /**
     * Listar altas curadas
     */
    public function curadas()
    {
        $altas = Alta::curada()
            ->with('consulta')
            ->orderBy('data_hora_alta', 'desc')
            ->get();

        return response()->json($altas);
    }

    /**
     * Listar altas que necessitam retorno
     */
    public function necessitamRetorno()
    {
        $altas = Alta::necessitamRetorno()
            ->with('consulta')
            ->orderBy('data_retorno', 'asc')
            ->get();

        return response()->json($altas);
    }

    /**
     * Listar altas com documentação pendente
     */
    public function pendentes()
    {
        $altas = Alta::pendentes()
            ->with('consulta')
            ->orderBy('data_hora_alta', 'asc')
            ->get();

        return response()->json($altas);
    }
}
