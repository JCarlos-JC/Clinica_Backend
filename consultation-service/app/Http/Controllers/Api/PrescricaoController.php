<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescricao;
use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrescricaoController extends Controller
{
    /**
     * Listar todas as prescrições
     */
    public function index(Request $request)
    {
        $query = Prescricao::with('consulta');

        // Filtros
        if ($request->has('nid')) {
            $query->porPaciente($request->nid);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('medicamento_controlado')) {
            $query->where('medicamento_controlado', $request->medicamento_controlado);
        }

        if ($request->has('ativas')) {
            $query->ativas();
        }

        if ($request->has('pendentes_dispensacao')) {
            $query->pendentesDispensacao();
        }

        if ($request->has('controladas')) {
            $query->controladas();
        }

        $prescricoes = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($prescricoes);
    }

    /**
     * Exibir uma prescrição específica
     */
    public function show($id)
    {
        $prescricao = Prescricao::with('consulta')->findOrFail($id);
        return response()->json($prescricao);
    }

    /**
     * Criar nova prescrição
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consulta_id' => 'required|exists:consultas,id',
            'nid' => 'required|string',
            'medicamento' => 'required|string|max:255',
            'dosagem' => 'required|string|max:255',
            'via_administracao' => 'required|string',
            'frequencia' => 'required|string|max:255',
            'duracao_tratamento' => 'required|integer|min:1',
            'unidade_duracao' => 'required|string',
            'quantidade_total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $prescricao = Prescricao::create($request->all());

        return response()->json([
            'message' => 'Prescrição criada com sucesso',
            'data' => $prescricao
        ], 201);
    }

    /**
     * Atualizar prescrição
     */
    public function update(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'medicamento' => 'sometimes|string|max:255',
            'dosagem' => 'sometimes|string|max:255',
            'frequencia' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $prescricao->update($request->all());

        return response()->json([
            'message' => 'Prescrição atualizada com sucesso',
            'data' => $prescricao
        ]);
    }

    /**
     * Deletar prescrição
     */
    public function destroy($id)
    {
        $prescricao = Prescricao::findOrFail($id);
        $prescricao->delete();

        return response()->json([
            'message' => 'Prescrição removida com sucesso'
        ]);
    }

    /**
     * Dispensar medicamento
     */
    public function dispensar(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'quantidade' => 'required|numeric|min:0',
            'local_dispensacao' => 'required|string',
            'dispensado_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $prescricao->dispensar(
                $request->quantidade,
                $request->local_dispensacao,
                $request->dispensado_por
            );

            return response()->json([
                'message' => 'Medicamento dispensado com sucesso',
                'data' => $prescricao->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao dispensar medicamento',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancelar prescrição
     */
    public function cancelar(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $prescricao->cancelar($request->motivo);

            return response()->json([
                'message' => 'Prescrição cancelada com sucesso',
                'data' => $prescricao->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cancelar prescrição',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Substituir prescrição
     */
    public function substituir(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nova_prescricao_id' => 'required|exists:prescricoes,id',
            'motivo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $prescricao->substituir(
                $request->nova_prescricao_id,
                $request->motivo
            );

            return response()->json([
                'message' => 'Prescrição substituída com sucesso',
                'data' => $prescricao->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao substituir prescrição',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Listar prescrições de uma consulta
     */
    public function porConsulta($consultaId)
    {
        $consulta = Consulta::findOrFail($consultaId);
        $prescricoes = $consulta->prescricoes()->orderBy('created_at', 'desc')->get();

        return response()->json($prescricoes);
    }

    /**
     * Listar prescrições ativas de um paciente
     */
    public function ativasPorPaciente($nid)
    {
        $prescricoes = Prescricao::porPaciente($nid)
            ->ativas()
            ->with('consulta')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($prescricoes);
    }

    /**
     * Listar prescrições controladas pendentes
     */
    public function controladasPendentes()
    {
        $prescricoes = Prescricao::controladas()
            ->pendentesDispensacao()
            ->with('consulta')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($prescricoes);
    }
}
