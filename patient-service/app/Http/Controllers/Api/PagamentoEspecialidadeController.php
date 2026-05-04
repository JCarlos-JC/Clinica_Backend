<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagamentoEspecialidade;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PagamentoEspecialidadeController extends Controller
{
    /**
     * Registrar pagamento de transferência de especialidade
     * POST /api/pacientes/pagamento-especialidade
     */
    public function registrarPagamento(Request $request)
    {
        // Parse manual do JSON — $request->all() pode retornar [] com o servidor de desenvolvimento
        $raw = $request->getContent();
        $data = [];
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = $decoded;
            }
        }
        // Fallback para form-data / query string
        if (empty($data)) {
            $data = $request->all();
        }

        $validator = Validator::make($data, [
            'paciente_id' => 'required|integer|exists:pacientes,id',
            'consulta_id' => 'required|integer',
            'agendamento_id' => 'nullable|integer',
            'nid' => 'required|string|max:50',
            'especialidade_destino' => 'required|string',
            'medico_destino_id' => 'nullable|integer',
            'valor_consulta' => 'required|numeric|min:0',
            'metodo_pagamento_id' => 'required|integer',
            'observacoes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Usar array $data em todo o método (não $request->campo)
        $pacienteId        = $data['paciente_id'];
        $consultaId        = $data['consulta_id'];
        $agendamentoIdIn   = $data['agendamento_id'] ?? null;
        $nid               = $data['nid'];
        $especialidadeDest = $data['especialidade_destino'];
        $medicoDest        = $data['medico_destino_id'] ?? null;
        $valorConsulta     = $data['valor_consulta'];
        $metodoPagamento   = $data['metodo_pagamento_id'];
        $observacoes       = $data['observacoes'] ?? null;

        try {
            DB::beginTransaction();

            // Criar registro de pagamento
            $pagamento = PagamentoEspecialidade::create([
                'paciente_id'          => $pacienteId,
                'consulta_id'          => $consultaId,
                'agendamento_id'       => $agendamentoIdIn,
                'nid'                  => $nid,
                'especialidade_destino'=> $especialidadeDest,
                'medico_destino_id'    => $medicoDest,
                'valor_consulta'       => $valorConsulta,
                'metodo_pagamento_id'  => $metodoPagamento,
                'observacoes'          => $observacoes,
                'status_pagamento'     => 'confirmado',
                'data_pagamento'       => now()
            ]);

            // Atualizar status do paciente para transferido_especialidade
            $paciente = Paciente::findOrFail($pacienteId);
            $paciente->update([
                'status'              => 'transferido_especialidade',
                'status_pagamento'    => 'pago',
                'especialidade'       => $especialidadeDest,
                'medico'              => $medicoDest,
                'data_transferencia'  => now(),
                'metodo_pagamento_id' => $metodoPagamento,
                'data_pagamento'      => now()
            ]);

            // Configuração do consultation-service
            $consultationServiceUrl = env('CONSULTATION_SERVICE_URL', 'http://127.0.0.1:8007');
            $serviceToken = env('SERVICE_TOKEN', 'shared-secret-token');

            // 1. Atualizar status de pagamento da consulta
            try {
                $consultaResponse = Http::withHeaders([
                    'X-Service-Token' => $serviceToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])->put("{$consultationServiceUrl}/api/consultas/{$consultaId}/atualizar", [
                    'status_pagamento' => 'pago',
                    'forma_pagamento'  => (string) $metodoPagamento,
                    'data_pagamento'   => now()->toDateTimeString(),
                    'valor_consulta'   => $valorConsulta
                ]);

                if ($consultaResponse->successful()) {
                    Log::info('Status de pagamento da consulta atualizado', [
                        'consulta_id' => $consultaId,
                        'status_pagamento' => 'pago'
                    ]);
                } else {
                    Log::warning('Falha ao atualizar status de pagamento da consulta', [
                        'consulta_id' => $consultaId,
                        'status_code' => $consultaResponse->status(),
                        'response' => $consultaResponse->body()
                    ]);
                }
            } catch (\Exception $consultaError) {
                Log::error('Erro ao atualizar status de pagamento da consulta', [
                    'consulta_id' => $consultaId,
                    'error' => $consultaError->getMessage()
                ]);
            }

            // 2. Buscar agendamento_id da consulta e atualizar no triage-service
            try {
                // Primeiro, buscar a consulta para obter o agendamento_id
                $consultaDataResponse = Http::withHeaders([
                    'X-Service-Token' => $serviceToken,
                    'Accept' => 'application/json'
                ])->get("{$consultationServiceUrl}/api/consultas/{$consultaId}");

                if ($consultaDataResponse->successful()) {
                    $consultaData = $consultaDataResponse->json();
                    $agendamentoId = $consultaData['data']['agendamento_id'] ?? null;

                    if ($agendamentoId) {
                        // Atualizar o agendamento existente no triage-service
                        $triageServiceUrl = env('TRIAGE_SERVICE_URL', 'http://127.0.0.1:8005');
                        
                        $updateResponse = Http::withHeaders([
                            'X-Service-Token' => $serviceToken,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json'
                        ])->patch("{$triageServiceUrl}/api/agendamentos/{$agendamentoId}/pagamento", [
                            'status'          => 'confirmada',
                            'status_pagamento' => 'pago',
                            'tipo'            => 'transferencia_especialidade'
                        ]);

                        if ($updateResponse->successful()) {
                            // Salvar agendamento_id no pagamento
                            $pagamento->update(['agendamento_id' => $agendamentoId]);

                            Log::info('Agendamento atualizado após pagamento', [
                                'pagamento_id' => $pagamento->id,
                                'agendamento_id' => $agendamentoId,
                                'tipo' => 'transferencia_especialidade'
                            ]);
                        } else {
                            Log::warning('Falha ao atualizar agendamento no triage-service', [
                                'agendamento_id' => $agendamentoId,
                                'status_code' => $updateResponse->status(),
                                'response' => $updateResponse->body()
                            ]);
                        }
                    } else {
                        Log::warning('Consulta não possui agendamento_id', [
                            'consulta_id' => $consultaId
                        ]);
                    }
                } else {
                    Log::warning('Falha ao buscar dados da consulta', [
                        'consulta_id' => $consultaId,
                        'status_code' => $consultaDataResponse->status()
                    ]);
                }
            } catch (\Exception $agendamentoError) {
                Log::error('Erro ao atualizar agendamento após pagamento', [
                    'consulta_id' => $consultaId,
                    'error' => $agendamentoError->getMessage()
                ]);
                // Não falhamos a transação por erro no agendamento
            }

            DB::commit();

            Log::info('Pagamento de especialidade registrado e paciente transferido', [
                'pagamento_id' => $pagamento->id,
                'paciente_id'  => $pacienteId,
                'consulta_id'  => $consultaId,
                'valor'        => $valorConsulta,
                'status_paciente' => 'transferido_especialidade'
            ]);

            // Recarregar o pagamento com o relacionamento
            $pagamento->load('paciente');

            return response()->json([
                'status' => 'success',
                'message' => 'Pagamento registrado com sucesso',
                'data' => $pagamento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao registrar pagamento de especialidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao registrar pagamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar pagamentos de um paciente
     * GET /api/pacientes/{id}/pagamentos-especialidade
     */
    public function listarPorPaciente(string $pacienteId)
    {
        try {
            $pagamentos = PagamentoEspecialidade::with('paciente')
                ->porPaciente($pacienteId)
                ->orderBy('data_pagamento', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $pagamentos
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar pagamentos de especialidade', [
                'paciente_id' => $pacienteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar pagamentos'
            ], 500);
        }
    }

    /**
     * Buscar pagamento por consulta
     * GET /api/pacientes/pagamento-especialidade/consulta/{consultaId}
     */
    public function buscarPorConsulta(string $consultaId)
    {
        try {
            $pagamento = PagamentoEspecialidade::with('paciente')
                ->porConsulta($consultaId)
                ->first();

            if (!$pagamento) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pagamento não encontrado para esta consulta'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $pagamento
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar pagamento por consulta', [
                'consulta_id' => $consultaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar pagamento'
            ], 500);
        }
    }

    /**
     * Listar todos os pagamentos (com filtros)
     * GET /api/pacientes/pagamentos-especialidade
     */
    public function index(Request $request)
    {
        try {
            $query = PagamentoEspecialidade::with('paciente');

            if ($request->has('status_pagamento')) {
                $query->where('status_pagamento', $request->status_pagamento);
            }

            if ($request->has('paciente_id')) {
                $query->porPaciente($request->paciente_id);
            }

            if ($request->has('nid')) {
                $query->where('nid', 'like', '%' . $request->nid . '%');
            }

            $pagamentos = $query->orderBy('data_pagamento', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $pagamentos
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar pagamentos de especialidade', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar pagamentos'
            ], 500);
        }
    }

    /**
     * Buscar pagamento por ID
     * GET /api/pacientes/pagamentos-especialidade/{id}
     */
    public function show(string $id)
    {
        try {
            $pagamento = PagamentoEspecialidade::with('paciente')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $pagamento
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pagamento não encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pagamento', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar pagamento'
            ], 500);
        }
    }
}
