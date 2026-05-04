<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LabColheitaController extends Controller
{
    /**
     * Iniciar colheita
     * POST /api/laboratorio/colheitas/{id}/iniciar
     */
    public function iniciar(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tecnico_id' => 'nullable|integer',
            'observacoes' => 'nullable|string|max:2000',
            'hora_inicio' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

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

            if ($agendamento->status !== 'agendada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Agendamento não está com status agendada'
                ], 400);
            }

            DB::table('agendamentos_colheita')
                ->where('id', $id)
                ->update([
                    'status' => 'em_colheita',
                    'tecnico_id' => $request->tecnico_id ?? $agendamento->tecnico_id,
                    'hora_inicio' => $request->hora_inicio ?? now()->format('H:i'),
                    'observacoes_colheita' => $request->observacoes,
                    'updated_at' => now(),
                ]);

            // Atualizar status dos exames
            DB::table('exames_agendamento_colheita')
                ->where('agendamento_colheita_id', $id)
                ->update([
                    'status' => 'em_colheita',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Colheita iniciada',
                'data' => [
                    'agendamento_id' => $id,
                    'status' => 'em_colheita'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar colheita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Concluir colheita e enviar resultados ao consultation-service
     * POST /api/laboratorio/colheitas/{id}/concluir
     */
    public function concluir(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tecnico_id'                       => 'nullable|integer',
            'hora_conclusao'                   => 'nullable|string|max:10',
            'observacoes_gerais'               => 'nullable|string|max:2000',
            'resultados'                       => 'required|array|min:1',
            'resultados.*.exame_id'            => 'nullable|integer',
            'resultados.*.tipo_exame'          => 'required|string',
            'resultados.*.resultado'           => 'required',
            'resultados.*.laudo'               => 'nullable|string|max:2000',
            'resultados.*.valores_referencia'  => 'nullable|array',
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

            $agendamento = DB::table('agendamentos_colheita')
                ->where('id', $id)
                ->first();

            if (!$agendamento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agendamento não encontrado'
                ], 404);
            }

            // Atualizar agendamento
            DB::table('agendamentos_colheita')
                ->where('id', $id)
                ->update([
                    'status' => 'concluida',
                    'tecnico_id' => $request->tecnico_id ?? $agendamento->tecnico_id,
                    'hora_conclusao' => $request->hora_conclusao ?? now()->format('H:i'),
                    'observacoes_conclusao' => $request->observacoes_gerais,
                    'data_conclusao' => now(),
                    'updated_at' => now(),
                ]);

            // Salvar resultados dos exames
            foreach ($request->resultados as $resultado) {
                $exameId = DB::table('exames_agendamento_colheita')
                    ->where('agendamento_colheita_id', $id)
                    ->where('tipo_exame', $resultado['tipo_exame'])
                    ->value('id');

                if ($exameId) {
                    DB::table('exames_agendamento_colheita')
                        ->where('id', $exameId)
                        ->update([
                            'status' => 'concluido',
                            'resultado' => json_encode($resultado['resultado']),
                            'laudo' => $resultado['laudo'] ?? null,
                            'valores_referencia' => isset($resultado['valores_referencia']) 
                                ? json_encode($resultado['valores_referencia']) 
                                : null,
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::commit();

            // Notificar consultation-service com os resultados
            if ($agendamento->consulta_id) {
                $this->notificarConsultationService($agendamento, $request->resultados);
            }

            Log::info('Colheita concluída', [
                'agendamento_id' => $id,
                'consulta_id' => $agendamento->consulta_id,
                'paciente_id' => $agendamento->paciente_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Colheita concluída. Resultados enviados ao médico.',
                'data' => [
                    'agendamento_id' => $id,
                    'status' => 'concluida',
                    'consulta_id' => $agendamento->consulta_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao concluir colheita', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao concluir colheita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notificar consultation-service com resultados
     */
    private function notificarConsultationService($agendamento, $resultados)
    {
        $consultationServiceUrl = env('CONSULTATION_SERVICE_URL', 'http://127.0.0.1:8007');
        $serviceToken = env('SERVICE_TOKEN', 'shared-secret-token');

        try {
            $response = Http::withHeaders([
                'X-Service-Token' => $serviceToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post("{$consultationServiceUrl}/api/consultas/retorno-exames/notificar", [
                'consulta_id' => $agendamento->consulta_id,
                'paciente_id' => $agendamento->paciente_id,
                'agendamento_colheita_id' => $agendamento->id,
                'resultados' => $resultados,
            ]);

            if ($response->successful()) {
                Log::info('Resultados enviados ao consultation-service', [
                    'consulta_id' => $agendamento->consulta_id,
                    'agendamento_id' => $agendamento->id
                ]);
            } else {
                Log::warning('Falha ao enviar resultados ao consultation-service', [
                    'consulta_id' => $agendamento->consulta_id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao notificar consultation-service', [
                'consulta_id' => $agendamento->consulta_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Adicionar anexo ao agendamento
     * POST /api/laboratorio/colheitas/{id}/anexo
     */
    public function adicionarAnexo(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ficheiro' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'exame_id' => 'nullable|integer',
            'descricao' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

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

            // Salvar arquivo
            $file = $request->file('ficheiro');
            $path = $file->store('resultados_exames', 'public');

            // Registrar na base de dados
            $anexoId = DB::table('anexos_colheita')->insertGetId([
                'agendamento_colheita_id' => $id,
                'exame_id' => $request->exame_id,
                'descricao' => $request->descricao,
                'nome_original' => $file->getClientOriginalName(),
                'caminho' => $path,
                'tipo_mime' => $file->getMimeType(),
                'tamanho' => $file->getSize(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anexo adicionado com sucesso',
                'data' => [
                    'anexo_id' => $anexoId,
                    'caminho' => $path,
                    'url' => asset("storage/{$path}")
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar anexo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar agendamento de colheita
     * POST /api/laboratorio/colheitas/{id}/cancelar
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|max:1000',
            'notificar_consulta' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

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

            DB::table('agendamentos_colheita')
                ->where('id', $id)
                ->update([
                    'status' => 'cancelada',
                    'motivo_cancelamento' => $request->motivo,
                    'data_cancelamento' => now(),
                    'updated_at' => now(),
                ]);

            // Atualizar exames
            DB::table('exames_agendamento_colheita')
                ->where('agendamento_colheita_id', $id)
                ->update([
                    'status' => 'cancelado',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Agendamento cancelado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
