<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagamentoConsulta;
use App\Models\Paciente;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gerenciar pagamentos de consultas
 * 
 * Este controller implementa todas as operações relacionadas ao novo sistema
 * de pagamentos, incluindo:
 * - Histórico de pagamentos por paciente
 * - Relatórios financeiros
 * - Sistema de retornos
 * - Auditoria de transações
 */
class PagamentoConsultaController extends Controller
{
    protected ConfigurationService $configService;

    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Listar histórico de pagamentos de um paciente
     * 
     * @param string $pacienteId
     * @return JsonResponse
     */
    public function historicoPaciente($pacienteId): JsonResponse
    {
        try {
            // Buscar paciente por ID ou NID
            $paciente = null;
            if (str_contains($pacienteId, '/') || !ctype_digit($pacienteId)) {
                $paciente = Paciente::where('nid', $pacienteId)->firstOrFail();
            } else {
                $paciente = Paciente::findOrFail($pacienteId);
            }

            $historico = PagamentoConsulta::porPaciente($paciente->id)
                ->with(['pagamentoAnterior'])
                ->orderBy('data_pagamento', 'desc')
                ->get()
                ->map(function($pagamento) {
                    return [
                        'id' => $pagamento->id,
                        'recibo' => $pagamento->numero_recibo,
                        'data' => $pagamento->data_pagamento?->format('d/m/Y H:i'),
                        'tipo_pagamento' => $pagamento->tipo_pagamento,
                        'valor_original' => $pagamento->valor_original,
                        'desconto' => $pagamento->desconto,
                        'valor_pago' => $pagamento->valor_pago,
                        'valor_pago_formatado' => $pagamento->valor_pago_formatado,
                        'status' => $pagamento->status,
                        'isencao_aplicada' => $pagamento->isencao_aplicada,
                        'motivo_isencao' => $pagamento->motivo_isencao,
                        'permite_retorno' => $pagamento->permite_retorno,
                        'retorno_valido_ate' => $pagamento->data_limite_retorno?->format('d/m/Y'),
                        'pode_usar_retorno' => $pagamento->podeUsarRetorno(),
                        'usuario_nome' => $pagamento->usuario_nome,
                        'observacoes' => $pagamento->observacoes,
                        'created_at' => $pagamento->created_at->format('d/m/Y H:i'),
                    ];
                });

            // Verificar retorno disponível
            $retornoDisponivel = PagamentoConsulta::temRetornoDisponivel($paciente->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'paciente' => [
                        'id' => $paciente->id,
                        'nid' => $paciente->nid,
                        'nome' => $paciente->nome,
                        'apelido' => $paciente->apelido,
                    ],
                    'historico' => $historico,
                    'resumo' => [
                        'total_consultas' => $historico->count(),
                        'total_pago' => $historico->sum('valor_pago'),
                        'total_isencoes' => $historico->where('isencao_aplicada', true)->count(),
                        'tem_retorno_disponivel' => $retornoDisponivel !== null,
                        'retorno_valido_ate' => $retornoDisponivel?->data_limite_retorno?->format('d/m/Y'),
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar histórico: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico de pagamentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar se paciente tem retorno disponível
     * 
     * @param string $pacienteId
     * @return JsonResponse
     */
    public function verificarRetorno($pacienteId): JsonResponse
    {
        try {
            $paciente = null;
            if (str_contains($pacienteId, '/') || !ctype_digit($pacienteId)) {
                $paciente = Paciente::where('nid', $pacienteId)->firstOrFail();
            } else {
                $paciente = Paciente::findOrFail($pacienteId);
            }

            $retorno = PagamentoConsulta::temRetornoDisponivel($paciente->id);

            if ($retorno && $retorno->podeUsarRetorno()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tem_retorno' => true,
                        'pagamento_original' => [
                            'id' => $retorno->id,
                            'recibo' => $retorno->numero_recibo,
                            'data_pagamento' => $retorno->data_pagamento->format('d/m/Y H:i'),
                            'valor_pago' => $retorno->valor_pago_formatado,
                            'tipo_consulta_id' => $retorno->tipo_consulta_id,
                        ],
                        'valido_ate' => $retorno->data_limite_retorno->format('d/m/Y'),
                        'dias_restantes' => now()->diffInDays($retorno->data_limite_retorno),
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tem_retorno' => false,
                    'message' => 'Nenhum retorno disponível para este paciente',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar retorno',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Relatório financeiro
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function relatorioFinanceiro(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date|after_or_equal:data_inicio',
                'tipo_pagamento' => 'nullable|in:consulta_regular,retorno,consulta_urgencia,consulta_especialidade,acompanhamento',
                'status' => 'nullable|in:pendente,pago,isento,cancelado,reembolsado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $dataInicio = $request->input('data_inicio', now()->startOfMonth());
            $dataFim = $request->input('data_fim', now()->endOfMonth());

            $query = PagamentoConsulta::porPeriodo($dataInicio, $dataFim);

            if ($request->filled('tipo_pagamento')) {
                $query->where('tipo_pagamento', $request->tipo_pagamento);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $relatorio = [
                'periodo' => [
                    'inicio' => $dataInicio,
                    'fim' => $dataFim,
                ],
                'totais' => [
                    'pagamentos' => $query->count(),
                    'arrecadado' => $query->sum('valor_pago'),
                    'valor_original' => $query->sum('valor_original'),
                    'descontos' => $query->sum('desconto'),
                    'isencoes' => $query->where('isencao_aplicada', true)->count(),
                ],
                'por_status' => PagamentoConsulta::porPeriodo($dataInicio, $dataFim)
                    ->select('status', DB::raw('COUNT(*) as total, SUM(valor_pago) as valor'))
                    ->groupBy('status')
                    ->get(),
                'por_tipo_pagamento' => PagamentoConsulta::porPeriodo($dataInicio, $dataFim)
                    ->select('tipo_pagamento', DB::raw('COUNT(*) as total, SUM(valor_pago) as valor'))
                    ->groupBy('tipo_pagamento')
                    ->get(),
                'por_metodo_pagamento' => PagamentoConsulta::porPeriodo($dataInicio, $dataFim)
                    ->select('metodo_pagamento_id', DB::raw('COUNT(*) as total, SUM(valor_pago) as valor'))
                    ->groupBy('metodo_pagamento_id')
                    ->get(),
                'por_dia' => PagamentoConsulta::porPeriodo($dataInicio, $dataFim)
                    ->select(
                        DB::raw('DATE(data_pagamento) as data'),
                        DB::raw('COUNT(*) as total'),
                        DB::raw('SUM(valor_pago) as arrecadado'),
                        DB::raw('SUM(CASE WHEN isencao_aplicada THEN 1 ELSE 0 END) as isencoes')
                    )
                    ->groupBy('data')
                    ->orderBy('data')
                    ->get(),
                'motivos_isencao' => PagamentoConsulta::isentos()
                    ->porPeriodo($dataInicio, $dataFim)
                    ->select('motivo_isencao', DB::raw('COUNT(*) as total'))
                    ->whereNotNull('motivo_isencao')
                    ->groupBy('motivo_isencao')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $relatorio
            ]);

        } catch (\Exception $e) {
            Log::error("Erro no relatório financeiro: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar relatório',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar pagamentos com filtros
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PagamentoConsulta::with(['paciente']);

            // Filtros
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('tipo_pagamento')) {
                $query->where('tipo_pagamento', $request->tipo_pagamento);
            }

            if ($request->filled('data_inicio') && $request->filled('data_fim')) {
                $query->porPeriodo($request->data_inicio, $request->data_fim);
            }

            if ($request->filled('paciente_nid')) {
                $query->where('paciente_nid', 'LIKE', "%{$request->paciente_nid}%");
            }

            if ($request->filled('numero_recibo')) {
                $query->where('numero_recibo', 'LIKE', "%{$request->numero_recibo}%");
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'data_pagamento');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginação
            $perPage = $request->get('per_page', 15);
            $pagamentos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pagamentos->items(),
                'meta' => [
                    'current_page' => $pagamentos->currentPage(),
                    'last_page' => $pagamentos->lastPage(),
                    'per_page' => $pagamentos->perPage(),
                    'total' => $pagamentos->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao listar pagamentos: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar pagamentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detalhes de um pagamento específico
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $pagamento = PagamentoConsulta::with(['paciente', 'pagamentoAnterior', 'retornos'])
                ->findOrFail($id);

            // Buscar informações completas via ConfigurationService
            $tipoConsulta = null;
            $metodoPagamento = null;

            try {
                $tipoConsulta = $this->configService->getTipoConsulta($pagamento->tipo_consulta_id);
                $metodoPagamento = $this->configService->getMetodoPagamento($pagamento->metodo_pagamento_id);
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar dados de configuração: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pagamento' => [
                        'id' => $pagamento->id,
                        'numero_recibo' => $pagamento->numero_recibo,
                        'paciente' => [
                            'id' => $pagamento->paciente->id,
                            'nid' => $pagamento->paciente->nid,
                            'nome' => $pagamento->paciente->nome,
                            'apelido' => $pagamento->paciente->apelido,
                        ],
                        'tipo_consulta' => $tipoConsulta,
                        'metodo_pagamento' => $metodoPagamento,
                        'valor_original' => $pagamento->valor_original,
                        'desconto' => $pagamento->desconto,
                        'valor_pago' => $pagamento->valor_pago,
                        'valor_pago_formatado' => $pagamento->valor_pago_formatado,
                        'status' => $pagamento->status,
                        'tipo_pagamento' => $pagamento->tipo_pagamento,
                        'isencao_aplicada' => $pagamento->isencao_aplicada,
                        'motivo_isencao' => $pagamento->motivo_isencao,
                        'data_pagamento' => $pagamento->data_pagamento,
                        'data_vencimento' => $pagamento->data_vencimento,
                        'usuario_nome' => $pagamento->usuario_nome,
                        'numero_referencia' => $pagamento->numero_referencia,
                        'observacoes' => $pagamento->observacoes,
                        'permite_retorno' => $pagamento->permite_retorno,
                        'data_limite_retorno' => $pagamento->data_limite_retorno,
                        'pode_usar_retorno' => $pagamento->podeUsarRetorno(),
                        'pagamento_anterior' => $pagamento->pagamentoAnterior ? [
                            'id' => $pagamento->pagamentoAnterior->id,
                            'numero_recibo' => $pagamento->pagamentoAnterior->numero_recibo,
                            'data_pagamento' => $pagamento->pagamentoAnterior->data_pagamento,
                        ] : null,
                        'retornos' => $pagamento->retornos->map(function($retorno) {
                            return [
                                'id' => $retorno->id,
                                'numero_recibo' => $retorno->numero_recibo,
                                'data_pagamento' => $retorno->data_pagamento,
                                'status' => $retorno->status,
                            ];
                        }),
                        'created_at' => $pagamento->created_at,
                        'updated_at' => $pagamento->updated_at,
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar pagamento: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar detalhes do pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar um pagamento
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'motivo' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pagamento = PagamentoConsulta::findOrFail($id);

            if ($pagamento->status === 'cancelado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento já está cancelado',
                ], 400);
            }

            DB::beginTransaction();
            $pagamento->cancelar(
                $request->motivo,
                Auth::id()
            );
           

            DB::commit();

            Log::info("Pagamento cancelado: {$pagamento->numero_recibo}", [
                'pagamento_id' => $pagamento->id,
                'motivo' => $request->motivo,
                'usuario' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento cancelado com sucesso',
                'data' => [
                    'pagamento' => [
                        'id' => $pagamento->id,
                        'numero_recibo' => $pagamento->numero_recibo,
                        'status' => $pagamento->status,
                        'observacoes' => $pagamento->observacoes,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar pagamento: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplicar desconto em um pagamento
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function aplicarDesconto(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'desconto' => 'required|numeric|min:0',
                'motivo' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pagamento = PagamentoConsulta::findOrFail($id);

            if ($pagamento->status !== 'pendente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Apenas pagamentos pendentes podem ter desconto aplicado',
                ], 400);
            }

            if ($request->desconto > $pagamento->valor_original) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desconto não pode ser maior que o valor original',
                ], 400);
            }

            DB::beginTransaction();

            $pagamento->aplicarDesconto(
                $request->desconto,
                $request->motivo
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Desconto aplicado com sucesso',
                'data' => [
                    'pagamento' => [
                        'id' => $pagamento->id,
                        'valor_original' => $pagamento->valor_original,
                        'desconto' => $pagamento->desconto,
                        'valor_pago' => $pagamento->valor_pago,
                        'valor_pago_formatado' => $pagamento->valor_pago_formatado,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao aplicar desconto: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aplicar desconto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter configuração de pagamento para uma consulta
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function configuracaoPagamento(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'paciente_id' => 'nullable|integer',
                'paciente_nid' => 'nullable|string',
                'tipo_consulta_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paciente = null;
            $tipoUtenteId = null;

            // Buscar paciente se fornecido
            if ($request->filled('paciente_id')) {
                $paciente = Paciente::find($request->paciente_id);
            } elseif ($request->filled('paciente_nid')) {
                $paciente = Paciente::where('nid', $request->paciente_nid)->first();
            }

            if ($paciente) {
                $tipoUtenteId = $paciente->tipo_utente_id;
            }

            // Buscar informações do tipo de consulta
            $tipoConsulta = null;
            try {
                $tipoConsulta = $this->configService->getTipoConsulta($request->tipo_consulta_id);
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar tipo consulta: " . $e->getMessage());
            }

            // Buscar valor da consulta
            $valorConsulta = 0;
            $valorInfo = null;
            if ($tipoUtenteId) {
                try {
                    $valorInfo = $this->configService->getValorConsulta(
                        $request->tipo_consulta_id,
                        $tipoUtenteId
                    );
                    $valorConsulta = $valorInfo['valor'] ?? 500; // Valor padrão
                } catch (\Exception $e) {
                    Log::warning("Erro ao buscar valor consulta: " . $e->getMessage());
                    $valorConsulta = 500; // Valor padrão
                }
            }

            // Verificar se é estudante bolseiro
            $isEstudanteBolseiro = false;
            if ($paciente && $tipoUtenteId) {
                try {
                    $tipoUtente = $this->configService->getTipoUtente($tipoUtenteId);
                    $isEstudanteBolseiro = isset($tipoUtente['codigo']) && 
                        strtolower($tipoUtente['codigo']) === 'est-b';
                } catch (\Exception $e) {
                    Log::warning("Erro ao buscar tipo utente: " . $e->getMessage());
                }
            }

            // Métodos de pagamento disponíveis
            $metodosPagamento = [
                [
                    'id' => 1,
                    'codigo' => 'dinheiro',
                    'nome' => 'Dinheiro',
                    'descricao' => 'Pagamento em dinheiro',
                    'ativo' => true,
                ],
                [
                    'id' => 2,
                    'codigo' => 'mpesa',
                    'nome' => 'M-Pesa',
                    'descricao' => 'Pagamento via M-Pesa',
                    'ativo' => true,
                ],
                [
                    'id' => 3,
                    'codigo' => 'emola',
                    'nome' => 'E-mola',
                    'descricao' => 'Pagamento via E-mola',
                    'ativo' => true,
                ],
                [
                    'id' => 4,
                    'codigo' => 'cartao',
                    'nome' => 'Cartão',
                    'descricao' => 'Cartão de crédito/débito',
                    'ativo' => true,
                ],
                [
                    'id' => 5,
                    'codigo' => 'transferencia',
                    'nome' => 'Transferência',
                    'descricao' => 'Transferência bancária',
                    'ativo' => true,
                ],
            ];

            // Se é estudante bolseiro, adicionar opção de isenção
            if ($isEstudanteBolseiro) {
                $metodosPagamento[] = [
                    'id' => 6,
                    'codigo' => 'isencao',
                    'nome' => 'Isenção',
                    'descricao' => 'Isenção (Estudante Bolseiro)',
                    'ativo' => true,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo_consulta' => [
                        'id' => $request->tipo_consulta_id,
                        'nome' => $tipoConsulta['nome'] ?? 'Consulta',
                        'descricao' => $tipoConsulta['descricao'] ?? null,
                    ],
                    'valor' => [
                        'original' => $valorConsulta,
                        'formatado' => number_format($valorConsulta, 2, ',', '.') . ' MT',
                        'isencao_disponivel' => $isEstudanteBolseiro,
                        'valor_com_isencao' => $isEstudanteBolseiro ? 0 : $valorConsulta,
                    ],
                    'paciente' => $paciente ? [
                        'id' => $paciente->id,
                        'nid' => $paciente->nid,
                        'nome' => $paciente->nome,
                        'tipo_utente_id' => $paciente->tipo_utente_id,
                        'is_estudante_bolseiro' => $isEstudanteBolseiro,
                    ] : null,
                    'metodos_pagamento' => $metodosPagamento,
                    'configuracao' => [
                        'permite_desconto' => true,
                        'desconto_maximo' => $valorConsulta * 0.1, // 10% máximo
                        'observacoes_obrigatorias' => $isEstudanteBolseiro,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Erro na configuração de pagamento: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter configuração de pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Processar pagamento de consulta
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processarPagamento(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'paciente_id' => 'nullable|integer',
                'tipo_consulta_id' => 'required|integer',
                'metodo_pagamento_id' => 'required|integer',
                'valor' => 'required|numeric|min:0',
                'isencao' => 'nullable|boolean',
                'observacoes' => 'nullable|string|max:500',
                'desconto' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar paciente
            $paciente = null;
            if ($request->filled('paciente_id')) {
                $paciente = Paciente::findOrFail($request->paciente_id);
            } elseif ($request->filled('paciente_nid')) {
                $paciente = Paciente::where('nid', $request->paciente_nid)->firstOrFail();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ID ou NID do paciente é obrigatório',
                ], 422);
            }

            DB::beginTransaction();

            // Buscar informações do tipo de utente para determinar valor
            $tipoUtente = null;
            $isEstudanteBolseiro = false;
            
            try {
                $tipoUtente = $this->configService->getTipoUtente($paciente->tipo_utente_id);
                $isEstudanteBolseiro = isset($tipoUtente['codigo']) && 
                    strtolower($tipoUtente['codigo']) === 'est-b';
                    
                Log::info('🎓 Informações do tipo de utente:', [
                    'tipo_utente' => $tipoUtente,
                    'is_estudante_bolseiro' => $isEstudanteBolseiro
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erro ao buscar tipo de utente:', ['error' => $e->getMessage()]);
            }

            // Determinar valor da consulta usando tabela preco_consultas
            $valorOriginal = $request->input('valor', 0);
            $valorFinal = 0;
            $isencaoAplicada = false;
            $valorConsultaInfo = null;
            $desconto = $request->input('desconto', 0);

            // Verificar se é isenção manual ou automática
            $isencaoManual = $request->boolean('isencao');

            // Sempre buscar o valor da tabela preco_consultas primeiro
            try {
                $valorConsultaInfo = $this->configService->getValorConsulta(
                    $request->tipo_consulta_id,
                    $paciente->tipo_utente_id
                );
                
                Log::info('📋 Informações da tabela preco_consultas:', [
                    'tipo_consulta_id' => $request->tipo_consulta_id,
                    'tipo_utente_id' => $paciente->tipo_utente_id,
                    'valor_info' => $valorConsultaInfo
                ]);
                
                if ($valorConsultaInfo && isset($valorConsultaInfo['valor'])) {
                    $valorOriginal = (float)$valorConsultaInfo['valor'];
                }
            } catch (\Exception $e) {
                Log::error('❌ Erro ao buscar valor na tabela preco_consultas:', [
                    'error' => $e->getMessage(),
                    'tipo_consulta_id' => $request->tipo_consulta_id,
                    'tipo_utente_id' => $paciente->tipo_utente_id
                ]);
            }

            // Aplicar lógica de isenção
            if ($isEstudanteBolseiro || $isencaoManual) {
                // Estudante bolseiro tem isenção automática ou isenção manual
                $valorFinal = 0;
                $isencaoAplicada = true;
                Log::info('✅ Isenção aplicada:', [
                    'tipo' => $isEstudanteBolseiro ? 'estudante_bolseiro' : 'manual',
                    'paciente_nid' => $paciente->nid
                ]);
            } else {
                // Para outros tipos, aplicar valor normal com desconto
                $valorFinal = max(0, $valorOriginal - $desconto);
                Log::info('💰 Valor calculado:', [
                    'valor_original' => $valorOriginal,
                    'desconto' => $desconto,
                    'valor_final' => $valorFinal
                ]);
            }

            // Preparar motivo de isenção (garantir string válida)
            $motivoIsencao = null;
            if ($isencaoAplicada) {
                if ($isEstudanteBolseiro) {
                    $motivoIsencao = 'Estudante bolseiro';
                } else {
                    $obs = $request->input('observacoes');
                    $motivoIsencao = (is_null($obs) || $obs === '') ? 'Isenção aplicada' : (string)$obs;
                }
            }

            // Criar pagamento
            $pagamento = PagamentoConsulta::create([
                'paciente_id' => $paciente->id,
                'paciente_nid' => $paciente->nid,
                'tipo_consulta_id' => $request->tipo_consulta_id,
                'metodo_pagamento_id' => $request->metodo_pagamento_id,
                'valor_original' => $valorOriginal,
                'desconto' => $desconto,
                'valor_pago' => $valorFinal,
                'status' => $isencaoAplicada ? 'isento' : 'pago',
                'tipo_pagamento' => 'consulta_regular',
                'isencao_aplicada' => $isencaoAplicada,
                'motivo_isencao' => $motivoIsencao,
                'data_pagamento' => now(),
                'observacoes' => $request->input('observacoes'),
            ]);

            // Marcar como pago ou isento
            if ($isencaoAplicada) {
                $pagamento->marcarComoIsento($motivoIsencao ?? 'Isenção aplicada');
            } else {
                $pagamento->marcarComoPago();
            }

            // Configurar retorno (30 dias padrão)
            $pagamento->configurarRetorno(30);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isencaoAplicada ? 'Isenção aplicada com sucesso' : 'Pagamento processado com sucesso',
                'data' => [
                    'pagamento' => [
                        'id' => $pagamento->id,
                        'numero_recibo' => $pagamento->numero_recibo,
                        'valor_original' => $pagamento->valor_original,
                        'desconto' => $pagamento->desconto,
                        'valor_pago' => $pagamento->valor_pago,
                        'valor_pago_formatado' => $pagamento->valor_pago_formatado,
                        'status' => $pagamento->status,
                        'isencao_aplicada' => $pagamento->isencao_aplicada,
                        'data_pagamento' => $pagamento->data_pagamento,
                        'permite_retorno' => $pagamento->permite_retorno,
                        'data_limite_retorno' => $pagamento->data_limite_retorno,
                    ],
                    'paciente' => [
                        'id' => $paciente->id,
                        'nid' => $paciente->nid,
                        'nome' => $paciente->nome,
                    ],
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao processar pagamento: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar métodos de pagamento disponíveis
     * 
     * @return JsonResponse
     */
    public function metodosPagamento(): JsonResponse
    {
        try {
            $metodos = [
                [
                    'id' => 1,
                    'codigo' => 'dinheiro',
                    'nome' => 'Dinheiro',
                    'descricao' => 'Pagamento em dinheiro',
                    'ativo' => true,
                    'icone' => '💵',
                ],
                [
                    'id' => 2,
                    'codigo' => 'mpesa',
                    'nome' => 'M-Pesa',
                    'descricao' => 'Pagamento via M-Pesa',
                    'ativo' => true,
                    'icone' => '📱',
                ],
                [
                    'id' => 3,
                    'codigo' => 'emola',
                    'nome' => 'E-mola',
                    'descricao' => 'Pagamento via E-mola',
                    'ativo' => true,
                    'icone' => '📲',
                ],
                [
                    'id' => 4,
                    'codigo' => 'cartao',
                    'nome' => 'Cartão',
                    'descricao' => 'Cartão de crédito/débito',
                    'ativo' => true,
                    'icone' => '💳',
                ],
                [
                    'id' => 5,
                    'codigo' => 'transferencia',
                    'nome' => 'Transferência',
                    'descricao' => 'Transferência bancária',
                    'ativo' => true,
                    'icone' => '🏦',
                ],
                [
                    'id' => 6,
                    'codigo' => 'isencao',
                    'nome' => 'Isenção',
                    'descricao' => 'Isenção (apenas estudantes bolseiros)',
                    'ativo' => true,
                    'icone' => '🎓',
                    'restricao' => 'Apenas para estudantes bolseiros',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $metodos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar métodos de pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Teste simples de processamento
     */
    public function testeProcessar(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint funcionando',
            'data_recebida' => $request->all(),
            'validacao' => [
                'paciente_nid' => $request->input('paciente_nid'),
                'tipo_consulta_id' => $request->input('tipo_consulta_id'),
                'metodo_pagamento_id' => $request->input('metodo_pagamento_id'),
                'valor' => $request->input('valor'),
                'isencao' => $request->input('isencao'),
            ]
        ]);
    }



    /**
     * Dashboard com estatísticas
     * 
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        try {
            $hoje = now()->toDateString();
            $mesAtual = now();

            $stats = [
                'hoje' => [
                    'pagamentos' => PagamentoConsulta::whereDate('data_pagamento', $hoje)->count(),
                    'arrecadado' => PagamentoConsulta::whereDate('data_pagamento', $hoje)->sum('valor_pago'),
                    'isencoes' => PagamentoConsulta::isentos()->whereDate('data_pagamento', $hoje)->count(),
                    'retornos' => PagamentoConsulta::retornos()->whereDate('data_pagamento', $hoje)->count(),
                ],
                'mes_atual' => [
                    'pagamentos' => PagamentoConsulta::whereMonth('data_pagamento', $mesAtual->month)
                        ->whereYear('data_pagamento', $mesAtual->year)->count(),
                    'arrecadado' => PagamentoConsulta::whereMonth('data_pagamento', $mesAtual->month)
                        ->whereYear('data_pagamento', $mesAtual->year)->sum('valor_pago'),
                    'media_diaria' => $mesAtual->day > 0 ? 
                        PagamentoConsulta::whereMonth('data_pagamento', $mesAtual->month)
                        ->whereYear('data_pagamento', $mesAtual->year)->sum('valor_pago') / $mesAtual->day : 0,
                ],
                'retornos_disponiveis' => PagamentoConsulta::comRetornoDisponivel()->count(),
                'por_status' => PagamentoConsulta::select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')->get()->toArray(),
                'top_5_pacientes_mes' => PagamentoConsulta::select('paciente_nid', DB::raw('COUNT(*) as consultas, SUM(valor_pago) as total_pago'))
                    ->whereMonth('data_pagamento', $mesAtual->month)
                    ->whereYear('data_pagamento', $mesAtual->year)
                    ->groupBy('paciente_nid')
                    ->orderBy('consultas', 'desc')
                    ->take(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error("Erro no dashboard: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar estatísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
