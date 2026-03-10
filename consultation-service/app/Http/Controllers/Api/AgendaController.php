<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Services\TriageServiceClient;
use App\Services\PatientServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AgendaController extends Controller
{
    protected $triageService;
    protected $patientService;
    protected static $tiposUtentesCache = null; // Cache estático para tipos de utentes

    public function __construct(
        TriageServiceClient $triageService,
        PatientServiceClient $patientService
    ) {
        $this->triageService = $triageService;
        $this->patientService = $patientService;
    }

    /**
     * Lista todos os agendamentos do triage-service
     * GET /api/agenda/agendamentos
     */
    public function listarAgendamentos(Request $request)
    {
        try {
            $filtros = $request->only([
                'status',
                'data_inicio',
                'data_fim',
                'medico_id',
                'especialidade',
                'prioridade',
                'page',
                'per_page'
            ]);

            Log::info('Buscando agendamentos do triage-service', [
                'filtros' => $filtros,
                'triage_url' => config('services.triage.url')
            ]);
            
            $agendamentos = $this->triageService->listarAgendamentos($filtros);
            
            // Enriquecer cada agendamento com dados do paciente e históricos
            if (isset($agendamentos['data']['data']) && is_array($agendamentos['data']['data'])) {
                foreach ($agendamentos['data']['data'] as &$agendamento) {
                    if (isset($agendamento['nid'])) {
                        $this->enriquecerAgendamento($agendamento, $request->bearerToken());
                    }
                }
            }
            
            Log::info('Agendamentos retornados e enriquecidos', [
                'count' => isset($agendamentos['data']['data']) ? count($agendamentos['data']['data']) : 0
            ]);

            return response()->json($agendamentos)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        } catch (\Exception $e) {
            Log::error('Erro ao listar agendamentos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar agendamentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enriquece um agendamento com dados completos do paciente e históricos
     */
    protected function enriquecerAgendamento(&$agendamento, $token = null)
    {
        try {
            $nid = $agendamento['nid'];

            // 1. DADOS DO PACIENTE
            // Usar dados que já vem no agendamento/triagem
            $triagem = $agendamento['triagem'] ?? [];
            
            // Buscar dados de contato e tipo de utente diretamente do banco (mesma DB)
            $dadosContato = null;
            $tipoUtente = null;
            if (isset($triagem['paciente_id'])) {
                try {
                    // Buscar dados do paciente
                    $dadosContato = DB::connection('mysql')
                        ->table('pacientes')
                        ->where('id', $triagem['paciente_id'])
                        ->select('email', 'celular as telefone', 'celular_alternativo as telefone_alternativo', 'avenida_rua_celula', 'bairro_id', 'tipo_utente_id')
                        ->first();
                    
                    // Buscar nome do tipo de utente via API do patient-service (com cache)
                    if ($dadosContato && $dadosContato->tipo_utente_id) {
                        // Usar cache se disponível
                        if (self::$tiposUtentesCache === null) {
                            try {
                                $response = $this->patientService->getTiposUtentes($token);
                                if ($response && isset($response['data'])) {
                                    self::$tiposUtentesCache = $response['data'];
                                } else {
                                    self::$tiposUtentesCache = [];
                                }
                            } catch (\Exception $e) {
                                Log::warning('Erro ao buscar tipos de utentes', ['error' => $e->getMessage()]);
                                self::$tiposUtentesCache = [];
                            }
                        }
                        
                        // Buscar o tipo correspondente
                        foreach (self::$tiposUtentesCache as $tipo) {
                            if ($tipo['id'] == $dadosContato->tipo_utente_id) {
                                $tipoUtente = $tipo['nome'];
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar dados de contato do paciente', [
                        'paciente_id' => $triagem['paciente_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Formatar endereço
            $endereco = null;
            if ($dadosContato && $dadosContato->avenida_rua_celula) {
                $endereco = $dadosContato->avenida_rua_celula;
            }
            
            $agendamento['paciente'] = [
                'nid' => $triagem['nid'] ?? $nid,
                'nome' => $triagem['nome'] ?? null,
                'apelido' => $triagem['apelido'] ?? null,
                'genero' => $triagem['genero'] ?? null,
                'data_nascimento' => $triagem['data_nascimento'] ?? null,
                'tipo_utente' => $tipoUtente ?? $triagem['tipo_utente'] ?? null,
                'email' => $dadosContato->email ?? null,
                'telefone' => $dadosContato->telefone ?? null,
                'telefone_alternativo' => $dadosContato->telefone_alternativo ?? null,
                'endereco' => $endereco,
            ];

            // 2. SINAIS VITAIS - buscar histórico do triage-service
            try {
                $sinaisVitais = $this->triageService->getHistoricoSinaisVitais($nid);
                $agendamento['historico_sinais_vitais'] = ['data' => $sinaisVitais];
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar histórico de sinais vitais', [
                    'nid' => $nid,
                    'error' => $e->getMessage()
                ]);
                $agendamento['historico_sinais_vitais'] = ['data' => []];
            }

            // 3. HISTÓRICO DE CONSULTAS (buscar do consultation service local)
            try {
                $consultasHistorico = \App\Models\Consulta::with('prescricoes')
                    ->where('nid', $nid)
                    ->where('status', '!=', 'cancelada') // Excluir canceladas
                    ->orderBy('data_hora_inicio', 'desc')
                    ->limit(10)
                    ->select([
                        'id', 'nid', 'data_hora_inicio', 'data_hora_fim',
                        'motivo_consulta', 'hipotese_diagnostica', 'status',
                        'medico_id', 'medico', 'especialidade'
                    ])
                    ->get()
                    ->toArray();
                
                $agendamento['historico_consultas'] = ['data' => $consultasHistorico];
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar histórico de consultas', [
                    'nid' => $nid,
                    'error' => $e->getMessage()
                ]);
                $agendamento['historico_consultas'] = ['data' => []];
            }

            // 4. HISTÓRICO DE EXAMES (buscar do consultation service local)
            try {
                $exames = \App\Models\Exame::where('nid', $nid)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->select([
                        'id', 'nid', 'nome_exame', 'tipo_exame', 'status',
                        'data_solicitacao', 'data_realizacao', 'resultado',
                        'medico_solicitante_id', 'medico_solicitante_nome'
                    ])
                    ->get()
                    ->toArray();
                
                $agendamento['historico_exames'] = ['data' => $exames];
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar histórico de exames', [
                    'nid' => $nid,
                    'error' => $e->getMessage()
                ]);
                $agendamento['historico_exames'] = ['data' => []];
            }

        } catch (\Exception $e) {
            Log::error('Erro ao enriquecer agendamento', [
                'nid' => $agendamento['nid'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback com estruturas vazias
            $agendamento['paciente'] = $agendamento['paciente'] ?? [
                'tipo_utente' => null,
                'email' => null,
                'telefone' => null,
                'telefone_alternativo' => null,
                'endereco' => null,
            ];
            $agendamento['historico_sinais_vitais'] = $agendamento['historico_sinais_vitais'] ?? ['data' => []];
            $agendamento['historico_exames'] = $agendamento['historico_exames'] ?? ['data' => []];
            $agendamento['historico_consultas'] = $agendamento['historico_consultas'] ?? ['data' => []];
        }
    }

    /**
     * Lista agendamentos pendentes de sincronização
     * GET /api/agenda/pendentes
     */
    public function listarPendentes(Request $request)
    {
        try {
            $agendamentos = $this->triageService->getAgendamentosPendentes();

            return response()->json($agendamentos);
        } catch (\Exception $e) {
            Log::error('Erro ao listar agendamentos pendentes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar agendamentos pendentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca detalhes de um agendamento específico
     * GET /api/agenda/agendamentos/{id}
     */
    public function buscarAgendamento(Request $request, $id)
    {
        try {
            $agendamento = $this->triageService->getAgendamento($id);

            if (!$agendamento) {
                return response()->json([
                    'message' => 'Agendamento não encontrado'
                ], 404);
            }

            // Verificar se já existe consulta para este agendamento
            $consulta = Consulta::where('agendamento_id', $id)->first();
            
            $agendamento['consulta_criada'] = $consulta ? true : false;
            $agendamento['consulta_id'] = $consulta ? $consulta->id : null;
            $agendamento['consulta_status'] = $consulta ? $consulta->status : null;

            return response()->json($agendamento);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar agendamento', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aceita um agendamento e cria a consulta
     * POST /api/agenda/agendamentos/{id}/aceitar
     */
    public function aceitarAgendamento(Request $request, $id)
    {
        try {
            // Verificar se já existe consulta para este agendamento
            $consultaExistente = Consulta::where('agendamento_id', $id)->first();
            
            if ($consultaExistente) {
                return response()->json([
                    'message' => 'Este agendamento já foi aceito',
                    'consulta' => $consultaExistente,
                    'consulta_id' => $consultaExistente->id
                ], 200); // Mudei para 200 para não falhar o processo
            }

            // Buscar agendamento do triage-service (com timeout reduzido)
            try {
                $agendamento = $this->triageService->getAgendamento($id);
            } catch (\Exception $e) {
                Log::warning('Falha ao buscar agendamento do triage-service', [
                    'agendamento_id' => $id,
                    'error' => $e->getMessage()
                ]);
                $agendamento = null;
            }
            
            if (!$agendamento) {
                return response()->json([
                    'message' => 'Agendamento não encontrado no triage-service',
                    'agendamento_id' => $id
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Criar consulta a partir do agendamento
                $consulta = Consulta::create([
                    'agendamento_id' => $id,
                    'nid' => $agendamento['nid'] ?? null,
                    'paciente_id' => $agendamento['paciente_id'] ?? null,
                    'triagem_id' => $agendamento['triagem_id'] ?? null,
                    'medico' => $agendamento['medico'] ?? null,
                    'medico_id' => $agendamento['medico_id'] ?? null,
                    'tipo_consulta' => $agendamento['tipo_consulta'] ?? 'primeira_vez',
                    'especialidade' => $agendamento['especialidade'] ?? null,
                    'especialidade_id' => $agendamento['especialidade_id'] ?? null,
                    'data_consulta' => $agendamento['data_consulta'] ?? $agendamento['data_agendamento'] ?? now(),
                    'hora_consulta' => $agendamento['hora_consulta'] ?? $agendamento['hora_agendamento'] ?? now(),
                    'motivo_consulta' => $agendamento['motivo_consulta'] ?? $agendamento['motivo'] ?? null,
                    'observacoes' => $agendamento['observacoes'] ?? null,
                    'status' => 'agendada',
                    'prioridade' => $agendamento['prioridade'] ?? 'normal',
                    'sincronizado_triagem' => false,
                ]);

                // Registrar no histórico
                $consulta->registrarHistorico(
                    'consulta_recebida',
                    null,
                    'agendada',
                    'Agendamento recebido do triage-service (ID: ' . $id . ')'
                );

                // Atualizar status no triage-service
                $this->triageService->atualizarStatusAgendamento(
                    $id,
                    'confirmado',
                    $consulta->id
                );

                // Marcar como sincronizado
                $this->triageService->marcarComoSincronizado($id);

                $consulta->sincronizado_triagem = true;
                $consulta->data_sincronizacao_triagem = now();
                $consulta->save();

                DB::commit();

                return response()->json([
                    'message' => 'Agendamento aceito com sucesso',
                    'consulta' => $consulta,
                    'agendamento' => $agendamento
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Erro ao aceitar agendamento', [
                'agendamento_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao aceitar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aceita múltiplos agendamentos de uma vez
     * POST /api/agenda/agendamentos/aceitar-multiplos
     */
    public function aceitarMultiplos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agendamento_ids' => 'required|array',
            'agendamento_ids.*' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $agendamentoIds = $request->agendamento_ids;
        $resultados = [
            'sucesso' => [],
            'erros' => []
        ];

        foreach ($agendamentoIds as $id) {
            try {
                $agendamento = $this->triageService->getAgendamento($id);
                
                if (!$agendamento) {
                    $resultados['erros'][] = [
                        'agendamento_id' => $id,
                        'mensagem' => 'Agendamento não encontrado'
                    ];
                    continue;
                }

                $consultaExistente = Consulta::where('agendamento_id', $id)->first();
                
                if ($consultaExistente) {
                    $resultados['erros'][] = [
                        'agendamento_id' => $id,
                        'mensagem' => 'Agendamento já foi aceito'
                    ];
                    continue;
                }

                DB::beginTransaction();

                $consulta = Consulta::create([
                    'agendamento_id' => $id,
                    'nid' => $agendamento['nid'] ?? null,
                    'paciente_id' => $agendamento['paciente_id'] ?? null,
                    'triagem_id' => $agendamento['triagem_id'] ?? null,
                    'medico' => $agendamento['medico'] ?? null,
                    'medico_id' => $agendamento['medico_id'] ?? null,
                    'tipo_consulta' => $agendamento['tipo_consulta'] ?? 'primeira_vez',
                    'especialidade' => $agendamento['especialidade'] ?? null,
                    'data_consulta' => $agendamento['data_agendamento'] ?? now(),
                    'hora_consulta' => $agendamento['hora_agendamento'] ?? now(),
                    'motivo_consulta' => $agendamento['motivo'] ?? null,
                    'status' => 'agendada',
                    'prioridade' => $agendamento['prioridade'] ?? 'normal',
                    'sincronizado_triagem' => false,
                ]);

                $consulta->registrarHistorico('consulta_recebida', null, 'agendada', 'Agendamento recebido do triage-service');

                $this->triageService->atualizarStatusAgendamento($id, 'confirmado', $consulta->id);
                $this->triageService->marcarComoSincronizado($id);

                $consulta->sincronizado_triagem = true;
                $consulta->data_sincronizacao_triagem = now();
                $consulta->save();

                DB::commit();

                $resultados['sucesso'][] = [
                    'agendamento_id' => $id,
                    'consulta_id' => $consulta->id
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                
                $resultados['erros'][] = [
                    'agendamento_id' => $id,
                    'mensagem' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Processamento concluído',
            'total' => count($agendamentoIds),
            'aceitos' => count($resultados['sucesso']),
            'erros_count' => count($resultados['erros']),
            'resultados' => $resultados
        ]);
    }

    /**
     * Recusa um agendamento
     * POST /api/agenda/agendamentos/{id}/recusar
     */
    public function recusarAgendamento(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Motivo da recusa é obrigatório',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Atualizar status no triage-service
            $atualizado = $this->triageService->atualizarStatusAgendamento(
                $id,
                'recusado',
                null
            );

            if (!$atualizado) {
                return response()->json([
                    'message' => 'Erro ao recusar agendamento no triage-service'
                ], 500);
            }

            Log::info('Agendamento recusado', [
                'agendamento_id' => $id,
                'motivo' => $request->motivo
            ]);

            return response()->json([
                'message' => 'Agendamento recusado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao recusar agendamento', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao recusar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca consultas criadas a partir de agendamentos
     * GET /api/agenda/consultas-agendadas
     */
    public function consultasAgendadas(Request $request)
    {
        try {
            $query = Consulta::whereNotNull('agendamento_id')
                ->with(['historico', 'anexos'])
                ->orderBy('data_consulta', 'desc')
                ->orderBy('hora_consulta', 'desc');

            // Filtros
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('data_inicio')) {
                $query->whereDate('data_consulta', '>=', $request->data_inicio);
            }

            if ($request->has('data_fim')) {
                $query->whereDate('data_consulta', '<=', $request->data_fim);
            }

            if ($request->has('nid')) {
                $query->where('nid', $request->nid);
            }

            $perPage = $request->get('per_page', 15);
            $consultas = $query->paginate($perPage);

            return response()->json($consultas);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas agendadas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar consultas agendadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza status de uma consulta com o triage-service
     * POST /api/agenda/consultas/{id}/sincronizar
     */
    public function sincronizarStatus(Request $request, $id)
    {
        try {
            $consulta = Consulta::findOrFail($id);

            if (!$consulta->agendamento_id) {
                return response()->json([
                    'message' => 'Esta consulta não possui agendamento vinculado'
                ], 400);
            }

            $token = $request->bearerToken();
            
            $statusMap = [
                'agendada' => 'confirmado',
                'em_atendimento' => 'em_atendimento',
                'finalizada' => 'concluido',
                'cancelada' => 'cancelado'
            ];

            $statusTriagem = $statusMap[$consulta->status] ?? 'confirmado';

            $atualizado = $this->triageService->atualizarStatusAgendamento(
                $consulta->agendamento_id,
                $statusTriagem,
                $consulta->id
            );

            if ($atualizado) {
                $consulta->sincronizado_triagem = true;
                $consulta->data_sincronizacao_triagem = now();
                $consulta->save();

                return response()->json([
                    'message' => 'Status sincronizado com sucesso',
                    'consulta' => $consulta
                ]);
            }

            return response()->json([
                'message' => 'Erro ao sincronizar status'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar status da consulta', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao sincronizar status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar agendamento (chamado pelo Patient Service após pagamento)
     * POST /api/agenda/agendamentos
     */
    public function criarAgendamento(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consulta_id' => 'required|integer|exists:consultas,id',
            'paciente_id' => 'required|integer',
            'medico_id' => 'required|integer',
            'especialidade' => 'required|string',
            'status' => 'required|string',
            'status_pagamento' => 'required|string',
            'valor_consulta' => 'required|numeric',
            'metodo_pagamento' => 'required|string',
            'data_agendamento' => 'required|date',
            'tipo' => 'nullable|in:triagem,transferencia_medico,transferencia_especialidade'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Verificar se consulta existe
            $consulta = Consulta::findOrFail($request->consulta_id);

            // Atualizar status da consulta
            $consulta->update([
                'status' => $request->status ?? 'agendado',
                'status_pagamento' => $request->status_pagamento,
                'valor_consulta' => $request->valor_consulta,
                'forma_pagamento' => $request->metodo_pagamento,
                'data_pagamento' => now()
            ]);

            // Se for transferência de especialidade, atualizar agendamento no triage-service
            if ($request->tipo === 'transferencia_especialidade' && $consulta->agendamento_id) {
                try {
                    // Atualizar agendamento existente no triage-service
                    $this->triageService->atualizarAgendamentoPagamento(
                        $consulta->agendamento_id,
                        [
                            'status' => 'confirmada',
                            'status_pagamento' => 'pago',
                            'tipo' => 'transferencia_especialidade'
                        ]
                    );

                    Log::info('Agendamento atualizado no triage-service', [
                        'agendamento_id' => $consulta->agendamento_id,
                        'consulta_id' => $consulta->id
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Erro ao atualizar agendamento no triage-service', [
                        'agendamento_id' => $consulta->agendamento_id,
                        'error' => $e->getMessage()
                    ]);
                    // Não falhar a operação se o triage-service não responder
                }
            }

            DB::commit();

            Log::info('Agendamento criado com sucesso', [
                'consulta_id' => $consulta->id,
                'tipo' => $request->tipo
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Agendamento criado com sucesso',
                'data' => $consulta->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao criar agendamento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar agendamento: ' . $e->getMessage()
            ], 500);
        }
    }
}

