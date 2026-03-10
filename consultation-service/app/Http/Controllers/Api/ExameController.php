<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exame;
use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExameController extends Controller
{
    /**
     * Listar todos os exames
     */
    public function index(Request $request)
    {
        $query = Exame::with('consulta');

        // Filtros
        if ($request->has('nid')) {
            $query->porPaciente($request->nid);
        }

        if ($request->has('tipo_exame')) {
            $query->porTipo($request->tipo_exame);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('urgentes')) {
            $query->urgentes();
        }

        if ($request->has('pendentes_laudo')) {
            $query->pendentesLaudo();
        }

        if ($request->has('disponiveis')) {
            $query->disponiveisParaVisualizacao();
        }

        $exames = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($exames);
    }

    /**
     * Exibir um exame específico
     */
    public function show($id)
    {
        $exame = Exame::with('consulta')->findOrFail($id);
        return response()->json($exame);
    }

    /**
     * Criar novo exame
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consulta_id' => 'required|exists:consultas,id',
            'nid' => 'required|string',
            'tipo_exame' => 'required|string',
            'nome_exame' => 'required|string|max:255',
            'categoria' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $exame = Exame::create($request->all());

        return response()->json([
            'message' => 'Exame criado com sucesso',
            'data' => $exame
        ], 201);
    }

    /**
     * Atualizar exame
     */
    public function update(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nome_exame' => 'sometimes|string|max:255',
            'tipo_exame' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $exame->update($request->all());

        return response()->json([
            'message' => 'Exame atualizado com sucesso',
            'data' => $exame
        ]);
    }

    /**
     * Deletar exame
     */
    public function destroy($id)
    {
        $exame = Exame::findOrFail($id);
        $exame->delete();

        return response()->json([
            'message' => 'Exame removido com sucesso'
        ]);
    }

    /**
     * Agendar exame
     */
    public function agendar(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'data_agendamento' => 'required|date',
            'hora_agendamento' => 'required',
            'local_realizacao' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exame->agendar(
                $request->data_agendamento,
                $request->hora_agendamento,
                $request->local_realizacao
            );

            return response()->json([
                'message' => 'Exame agendado com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao agendar exame',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Registrar coleta
     */
    public function registrarColeta(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'coletado_por' => 'required|string',
            'material_biologico' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exame->registrarColeta(
                $request->coletado_por,
                $request->material_biologico
            );

            return response()->json([
                'message' => 'Coleta registrada com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar coleta',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Iniciar análise
     */
    public function iniciarAnalise(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'analisado_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exame->iniciarAnalise(
                $request->analisado_por,
                $request->equipamento,
                $request->metodo
            );

            return response()->json([
                'message' => 'Análise iniciada com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao iniciar análise',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Registrar resultado
     */
    public function registrarResultado(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'resultados' => 'required|array',
            'valores_referencia' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exame->registrarResultado(
                $request->resultados,
                $request->valores_referencia
            );

            return response()->json([
                'message' => 'Resultado registrado com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar resultado',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Registrar laudo
     */
    public function registrarLaudo(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'laudado_por' => 'required|string',
            'laudo_medico' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exame->registrarLaudo(
                $request->laudado_por,
                $request->laudo_medico,
                $request->conclusao,
                $request->laudado_por_crm
            );

            return response()->json([
                'message' => 'Laudo registrado com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar laudo',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Disponibilizar exame
     */
    public function disponibilizar($id)
    {
        $exame = Exame::findOrFail($id);

        try {
            $exame->disponibilizar();

            return response()->json([
                'message' => 'Exame disponibilizado com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao disponibilizar exame',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Marcar como visualizado
     */
    public function marcarVisualizado($id)
    {
        $exame = Exame::findOrFail($id);

        try {
            $exame->marcarComoVisualizado();

            return response()->json([
                'message' => 'Exame marcado como visualizado',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao marcar exame',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancelar exame
     */
    public function cancelar(Request $request, $id)
    {
        $exame = Exame::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'motivo_cancelamento' => 'required|string',
            'cancelado_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exame->cancelar(
                $request->motivo_cancelamento,
                $request->cancelado_por
            );

            return response()->json([
                'message' => 'Exame cancelado com sucesso',
                'data' => $exame->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cancelar exame',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Listar exames de uma consulta
     */
    public function porConsulta($consultaId)
    {
        $consulta = Consulta::findOrFail($consultaId);
        $exames = $consulta->exames()->orderBy('created_at', 'desc')->get();

        return response()->json($exames);
    }

    /**
     * Listar exames urgentes
     */
    public function urgentes()
    {
        $exames = Exame::urgentes()
            ->with('consulta')
            ->orderBy('data_solicitacao', 'desc')
            ->get();

        return response()->json($exames);
    }

    /**
     * Listar exames pendentes de laudo
     */
    public function pendentesLaudo()
    {
        $exames = Exame::pendentesLaudo()
            ->with('consulta')
            ->orderBy('data_solicitacao', 'asc')
            ->get();

        return response()->json($exames);
    }

    /**
     * Listar exames pendentes (status: solicitado, agendado, em_coleta)
     * GET /api/exames/pendentes
     */
    public function pendentes(Request $request)
    {
        $query = Exame::whereIn('status', ['solicitado', 'agendado', 'em_coleta'])
            ->with('consulta');

        // Filtro por médico solicitante
        if ($request->has('medico_id')) {
            $query->where('medico_solicitante_id', $request->medico_id);
        }

        // Filtro por paciente
        if ($request->has('nid')) {
            $query->where('nid', $request->nid);
        }

        // Filtro por tipo de exame
        if ($request->has('tipo_exame')) {
            $query->where('tipo_exame', $request->tipo_exame);
        }

        $exames = $query->orderBy('data_solicitacao', 'asc')->paginate(15);

        return response()->json($exames);
    }
}
