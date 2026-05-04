<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transferencia;
use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransferenciaController extends Controller
{
    /**
     * Listar todas as transferências
     */
    public function index(Request $request)
    {
        $query = Transferencia::with('consulta');

        // Filtros
        if ($request->has('nid')) {
            $query->porPaciente($request->nid);
        }

        if ($request->has('tipo_transferencia')) {
            $query->where('tipo_transferencia', $request->tipo_transferencia);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('medico_origem_id')) {
            $query->porMedicoOrigem($request->medico_origem_id);
        }

        if ($request->has('medico_destino_id')) {
            $query->porMedicoDestino($request->medico_destino_id);
        }

        if ($request->has('solicitadas')) {
            $query->solicitadas();
        }

        if ($request->has('urgentes')) {
            $query->urgentes();
        }

        $transferencias = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($transferencias);
    }

    /**
     * Exibir uma transferência específica
     */
    public function show($id)
    {
        $transferencia = Transferencia::with('consulta')->findOrFail($id);
        return response()->json($transferencia);
    }

    /**
     * Criar nova transferência
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consulta_id' => 'required|exists:consultas,id',
            'nid' => 'required|string',
            'tipo_transferencia' => 'required|string',
            'motivo_transferencia' => 'required|string',
            'sumario_clinico' => 'required|string',
            'medico_destino_id' => 'sometimes|required_if:tipo_transferencia,entre_medicos',
            'especialidade_destino' => 'sometimes|required_if:tipo_transferencia,entre_especialidades',
            'hospital_destino' => 'sometimes|required_if:tipo_transferencia,entre_hospitais',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $transferencia = Transferencia::create($request->all());

        return response()->json([
            'message' => 'Transferência criada com sucesso',
            'data' => $transferencia
        ], 201);
    }

    /**
     * Atualizar transferência
     */
    public function update(Request $request, $id)
    {
        $transferencia = Transferencia::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tipo_transferencia' => 'sometimes|string',
            'motivo_transferencia' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $transferencia->update($request->all());

        return response()->json([
            'message' => 'Transferência atualizada com sucesso',
            'data' => $transferencia
        ]);
    }

    /**
     * Deletar transferência
     */
    public function destroy($id)
    {
        $transferencia = Transferencia::findOrFail($id);
        $transferencia->delete();

        return response()->json([
            'message' => 'Transferência removida com sucesso'
        ]);
    }

    /**
     * Aceitar transferência
     */
    public function aceitar(Request $request, $id)
    {
        $transferencia = Transferencia::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'aceita_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transferencia->aceitar($request->aceita_por);

            return response()->json([
                'message' => 'Transferência aceita com sucesso',
                'data' => $transferencia->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao aceitar transferência',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Recusar transferência
     */
    public function recusar(Request $request, $id)
    {
        $transferencia = Transferencia::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'motivo_recusa' => 'required|string',
            'recusada_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transferencia->recusar(
                $request->motivo_recusa,
                $request->recusada_por
            );

            return response()->json([
                'message' => 'Transferência recusada',
                'data' => $transferencia->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao recusar transferência',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Iniciar transporte
     */
    public function iniciarTransporte($id)
    {
        $transferencia = Transferencia::findOrFail($id);

        try {
            $transferencia->iniciarTransporte();

            return response()->json([
                'message' => 'Transporte iniciado',
                'data' => $transferencia->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao iniciar transporte',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Concluir transferência
     */
    public function concluir(Request $request, $id)
    {
        $transferencia = Transferencia::findOrFail($id);

        try {
            $transferencia->concluir($request->intercorrencias);

            return response()->json([
                'message' => 'Transferência concluída com sucesso',
                'data' => $transferencia->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao concluir transferência',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancelar transferência
     */
    public function cancelar(Request $request, $id)
    {
        $transferencia = Transferencia::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'motivo_cancelamento' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transferencia->cancelar($request->motivo_cancelamento);

            return response()->json([
                'message' => 'Transferência cancelada',
                'data' => $transferencia->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cancelar transferência',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Listar transferências de uma consulta
     */
    public function porConsulta($consultaId)
    {
        $consulta = Consulta::findOrFail($consultaId);
        $transferencias = $consulta->transferencias()->orderBy('created_at', 'desc')->get();

        return response()->json($transferencias);
    }

    /**
     * Listar transferências solicitadas
     */
    public function solicitadas()
    {
        $transferencias = Transferencia::solicitadas()
            ->with('consulta')
            ->orderBy('data_hora_solicitacao', 'desc')
            ->get();

        return response()->json($transferencias);
    }

    /**
     * Listar transferências urgentes
     */
    public function urgentes()
    {
        $transferencias = Transferencia::urgentes()
            ->with('consulta')
            ->orderBy('data_hora_solicitacao', 'desc')
            ->get();

        return response()->json($transferencias);
    }

    /**
     * Listar transferências aguardando transporte
     */
    public function aguardandoTransporte()
    {
        $transferencias = Transferencia::aguardandoTransporte()
            ->with('consulta')
            ->orderBy('data_hora_prevista', 'asc')
            ->get();

        return response()->json($transferencias);
    }

    /**
     * Listar transferências pendentes (solicitadas e aguardando aceitação)
     * GET /api/transferencias/pendentes
     */
    public function pendentes(Request $request)
    {
        $query = Transferencia::whereIn('status', ['solicitada', 'aguardando_transporte'])
            ->with('consulta');

        // Filtro por médico destino (para médicos que recebem transferências)
        if ($request->has('medico_destino_id')) {
            $query->where('medico_destino_id', $request->medico_destino_id);
        }

        // Filtro por médico origem
        if ($request->has('medico_origem_id')) {
            $query->where('medico_origem_id', $request->medico_origem_id);
        }

        // Filtro por tipo
        if ($request->has('tipo_transferencia')) {
            $query->where('tipo_transferencia', $request->tipo_transferencia);
        }

        $transferencias = $query->orderBy('data_hora_solicitacao', 'desc')->paginate(15);

        return response()->json($transferencias);
    }
}
