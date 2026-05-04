<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabAgendamentoController extends Controller
{
    /**
     * Listar agendamentos de colheita
     * GET /api/laboratorio/agendamentos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DB::table('agendamentos_colheita');

            // Filtrar por status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filtrar por data
            if ($request->has('data')) {
                $query->whereDate('data_colheita', $request->data);
            } else {
                // Por padrão, mostrar agendamentos de hoje
                $query->whereDate('data_colheita', today());
            }

            // Ordenar por hora
            $query->orderBy('data_colheita')->orderBy('hora_colheita');

            $agendamentos = $query->get();

            // Para cada agendamento, buscar os exames associados
            foreach ($agendamentos as $agendamento) {
                $agendamento->exames = DB::table('exames_agendamento_colheita')
                    ->where('agendamento_colheita_id', $agendamento->id)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $agendamentos
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar agendamentos de colheita', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar agendamentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar agendamentos pendentes (agendada ou em_colheita)
     * GET /api/laboratorio/agendamentos/pendentes
     */
    public function pendentes(Request $request): JsonResponse
    {
        try {
            $query = DB::table('agendamentos_colheita')
                ->whereIn('status', ['agendada', 'em_colheita'])
                ->whereDate('data_colheita', today())
                ->orderBy('hora_colheita');

            $agendamentos = $query->get();

            foreach ($agendamentos as $agendamento) {
                $agendamento->exames = DB::table('exames_agendamento_colheita')
                    ->where('agendamento_colheita_id', $agendamento->id)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $agendamentos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar agendamentos pendentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar agendamento específico
     * GET /api/laboratorio/agendamentos/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $agendamento = DB::table('agendamentos_colheita')
                ->where('id', $id)
                ->first();

            if (!$agendamento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agendamento não encontrado'
                ], 404);
            }

            $agendamento->exames = DB::table('exames_agendamento_colheita')
                ->where('agendamento_colheita_id', $agendamento->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $agendamento
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar agendamento de colheita (chamado pelo patient-service)
     * POST /api/laboratorio/agendamentos
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'solicitacao_exame_id' => 'required|integer',
            'consulta_id'          => 'nullable|integer',
            'paciente_id'          => 'required|integer',
            'nid'                  => 'required|string',
            'nome'                 => 'required|string',
            'data_colheita'        => 'required|date',
            'hora_colheita'        => 'required|string',
            'observacoes'          => 'nullable|string',
            'tecnico_id'           => 'nullable|integer',
            'exames'               => 'required|array|min:1',
            'exames.*.tipo_exame'  => 'required|string',
            'exames.*.prioridade'  => 'nullable|in:normal,urgente,critica',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Criar agendamento
            $agendamentoId = DB::table('agendamentos_colheita')->insertGetId([
                'solicitacao_exame_id' => $request->solicitacao_exame_id,
                'consulta_id'          => $request->consulta_id,
                'paciente_id'          => $request->paciente_id,
                'nid'                  => $request->nid,
                'nome'                 => $request->nome,
                'data_colheita'        => $request->data_colheita,
                'hora_colheita'        => $request->hora_colheita,
                'status'               => 'agendada',
                'observacoes'          => $request->observacoes,
                'tecnico_id'           => $request->tecnico_id,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // Criar registros dos exames
            foreach ($request->exames as $exame) {
                DB::table('exames_agendamento_colheita')->insert([
                    'agendamento_colheita_id' => $agendamentoId,
                    'tipo_exame'              => $exame['tipo_exame'],
                    'prioridade'              => $exame['prioridade'] ?? 'normal',
                    'status'                  => 'agendado',
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }

            DB::commit();

            Log::info('Agendamento de colheita criado', [
                'agendamento_id' => $agendamentoId,
                'paciente_id' => $request->paciente_id,
                'nid' => $request->nid
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agendamento criado com sucesso',
                'data' => [
                    'id' => $agendamentoId,
                    'status' => 'agendada',
                    'data_colheita' => $request->data_colheita,
                    'hora_colheita' => $request->hora_colheita
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao criar agendamento de colheita', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
