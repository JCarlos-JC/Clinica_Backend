<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitacaoExame;
use App\Models\Paciente;
// use App\Models\HistoricoPaciente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolicitacaoExameController extends Controller
{
    /**
     * Display a listing of solicitacoes.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Lê directamente da tabela `exames` (consultation-service)
            // que é a fonte de verdade dos exames solicitados em consulta
            $query = DB::table('exames')
                ->leftJoin('pacientes', 'pacientes.id', '=', 'exames.paciente_id')
                ->select(
                    'exames.id',
                    'exames.consulta_id',
                    'exames.nid as paciente_nid',
                    'exames.paciente_id',
                    DB::raw("CONCAT(COALESCE(pacientes.nome,''), ' ', COALESCE(pacientes.apelido,'')) as paciente_nome"),
                    'pacientes.nome as paciente_primeiro_nome',
                    'pacientes.apelido as paciente_ultimo_nome',
                    'exames.medico_solicitante_id as solicitante_id',
                    'exames.medico_solicitante_nome as medico_nome',
                    'exames.nome_exame',
                    'exames.tipo_exame',
                    'exames.descricao',
                    'exames.indicacao_clinica',
                    'exames.observacoes_solicitacao as observacoes',
                    'exames.data_solicitacao',
                    'exames.urgencia',
                    'exames.status',
                    'exames.created_at',
                    'exames.updated_at'
                )
                ->whereNull('exames.deleted_at');

            // Filtros
            if ($request->has('paciente_id')) {
                $query->where('exames.paciente_id', $request->paciente_id);
            }
            if ($request->has('nid')) {
                $query->where('exames.nid', $request->nid);
            }
            if ($request->has('status')) {
                $query->where('exames.status', $request->status);
            } else {
                // Por omissão mostra apenas os pendentes/solicitados
                $query->whereIn('exames.status', ['solicitado', 'agendado', 'aguardando_coleta']);
            }
            if ($request->has('consulta_id')) {
                $query->where('exames.consulta_id', $request->consulta_id);
            }
            if ($request->has('data_inicio')) {
                $query->whereDate('exames.data_solicitacao', '>=', $request->data_inicio);
            }
            if ($request->has('data_fim')) {
                $query->whereDate('exames.data_solicitacao', '<=', $request->data_fim);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('exames.nome_exame', 'like', "%{$search}%")
                      ->orWhere('exames.tipo_exame', 'like', "%{$search}%")
                      ->orWhere('exames.nid', 'like', "%{$search}%")
                      ->orWhere('pacientes.nome', 'like', "%{$search}%")
                      ->orWhere('pacientes.apelido', 'like', "%{$search}%");
                });
            }

            $sortBy    = $request->get('sort_by', 'exames.data_solicitacao');
            $sortOrder = $request->get('sort_order', 'desc');
            // Mapear nomes amigáveis para colunas reais
            $colMap = [
                'medico_nome'   => 'exames.medico_solicitante_nome',
                'paciente_nid'  => 'exames.nid',
                'observacoes'   => 'exames.observacoes_solicitacao',
                'data_solicitacao' => 'exames.data_solicitacao',
                'status'        => 'exames.status',
                'nome_exame'    => 'exames.nome_exame',
            ];
            $sortBy = $colMap[$sortBy] ?? $sortBy;
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);

            if ($request->has('paginate') && $request->paginate === 'false') {
                $exames = $query->get();
                return response()->json([
                    'success' => true,
                    'data'    => $exames,
                    'total'   => $exames->count(),
                ]);
            }

            $paginado = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $paginado->items(),
                'meta'    => [
                    'current_page' => $paginado->currentPage(),
                    'last_page'    => $paginado->lastPage(),
                    'per_page'     => $paginado->perPage(),
                    'total'        => $paginado->total(),
                    'from'         => $paginado->firstItem(),
                    'to'           => $paginado->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar solicitações de exame',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display solicitacoes of a specific patient.
     * 
     * @param int $pacienteId
     * @return JsonResponse
     */
    public function byPaciente(int $pacienteId, Request $request): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($pacienteId);
            
            $query = SolicitacaoExame::where('paciente_id', $pacienteId);

            // Filtros
            if ($request->has('status')) {
                $query->status($request->status);
            }

            if ($request->has('categoria')) {
                $query->categoria($request->categoria);
            }

            $query->orderBy('data_solicitacao', 'desc');

            $solicitacoes = $query->get();

            return response()->json([
                'success' => true,
                'data' => $solicitacoes,
                'paciente' => [
                    'id' => $paciente->id,
                    'nid' => $paciente->nid,
                    'nome_completo' => $paciente->nome_completo,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar solicitações',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created solicitacao.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validação - aceita APENAS paciente_nid (identificador único)
            $validator = Validator::make($request->all(), [
                'paciente_nid' => 'required|string|exists:pacientes,nid',
                'data_solicitacao' => 'required|date',
                'exames_solicitados' => 'required|json',
                'exames_realizaveis' => 'nullable|json',
                'exames_nao_realizaveis' => 'nullable|json',
                'categoria' => 'nullable|in:realizavel,nao_realizavel',
                'valor_total' => 'nullable|numeric|min:0',
                'pago' => 'nullable|boolean',
                'data_pagamento' => 'nullable|date',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Buscar o paciente pelo NID para registrar no histórico
            $paciente = Paciente::where('nid', $data['paciente_nid'])->firstOrFail();

            DB::beginTransaction();

            $solicitacao = SolicitacaoExame::create($data);

            // Registrar no histórico do paciente
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'exame',
            //     dadosNovos: $solicitacao->toArray(),
            //     observacao: "Solicitação de exame criada (ID: {$solicitacao->id})"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de exame criada com sucesso',
                'data' => $solicitacao->load('paciente'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar solicitação de exame',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified solicitacao.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $solicitacao = SolicitacaoExame::with('paciente')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $solicitacao,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de exame não encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar solicitação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified solicitacao.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $solicitacao = SolicitacaoExame::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'data_solicitacao' => 'sometimes|required|date',
                'exames_solicitados' => 'sometimes|required|json',
                'exames_realizaveis' => 'nullable|json',
                'exames_nao_realizaveis' => 'nullable|json',
                'categoria' => 'nullable|in:realizavel,nao_realizavel',
                'status' => 'nullable|in:pendente,em_andamento,concluido,cancelado',
                'valor_total' => 'nullable|numeric|min:0',
                'pago' => 'nullable|boolean',
                'data_pagamento' => 'nullable|date',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $dadosAnteriores = $solicitacao->toArray();
            
            $solicitacao->update($validator->validated());

            // Registrar no histórico do paciente
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $solicitacao->paciente_id,
            //     tipoOperacao: 'exame',
            //     dadosAnteriores: $dadosAnteriores,
            //     dadosNovos: $solicitacao->toArray(),
            //     observacao: "Solicitação de exame atualizada (ID: {$solicitacao->id})"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitação atualizada com sucesso',
                'data' => $solicitacao->fresh('paciente'),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de exame não encontrada',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar solicitação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified solicitacao (soft delete).
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $solicitacao = SolicitacaoExame::findOrFail($id);

            DB::beginTransaction();

            $dadosAnteriores = $solicitacao->toArray();
            $pacienteId = $solicitacao->paciente_id;
            
            $solicitacao->delete();

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $pacienteId,
            //     tipoOperacao: 'exame',
            //     dadosAnteriores: $dadosAnteriores,
            //     observacao: "Solicitação de exame excluída (ID: {$id})"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitação excluída com sucesso',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação não encontrada',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir solicitação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a soft deleted solicitacao.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $solicitacao = SolicitacaoExame::onlyTrashed()->findOrFail($id);

            DB::beginTransaction();

            $solicitacao->restore();

            // HistoricoPaciente::logOperacao(
            //     pacienteId: $solicitacao->paciente_id,
            //     tipoOperacao: 'exame',
            //     dadosNovos: $solicitacao->toArray(),
            //     observacao: "Solicitação de exame restaurada (ID: {$id})"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitação restaurada com sucesso',
                'data' => $solicitacao,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação não encontrada',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar solicitação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark solicitacao as paid.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'data_pagamento' => 'nullable|date',
                'valor_total' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $solicitacao = SolicitacaoExame::findOrFail($id);

            DB::beginTransaction();

            $solicitacao->marcarComoPago(
                $request->data_pagamento ?? now(),
                $request->valor_total
            );

            // HistoricoPaciente::logOperacao(
            //     pacienteId: $solicitacao->paciente_id,
            //     tipoOperacao: 'pagamento',
            //     dadosNovos: [
            //         'solicitacao_id' => $solicitacao->id,
            //         'pago' => true,
            //         'data_pagamento' => $solicitacao->data_pagamento,
            //         'valor_total' => $solicitacao->valor_total,
            //     ],
            //     observacao: "Pagamento registrado para solicitação de exame (ID: {$id})"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pagamento registrado com sucesso',
                'data' => $solicitacao,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação não encontrada',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change solicitacao status.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pendente,em_andamento,concluido,cancelado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $solicitacao = SolicitacaoExame::findOrFail($id);

            DB::beginTransaction();

            $statusAnterior = $solicitacao->status;
            $solicitacao->status = $request->status;
            $solicitacao->save();

            // HistoricoPaciente::logOperacao(
            //     pacienteId: $solicitacao->paciente_id,
            //     tipoOperacao: 'exame',
            //     dadosAnteriores: ['status' => $statusAnterior],
            //     dadosNovos: ['status' => $request->status],
            //     observacao: "Status da solicitação alterado de {$statusAnterior} para {$request->status} (ID: {$id})"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso',
                'data' => $solicitacao,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação não encontrada',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics.
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => SolicitacaoExame::count(),
                'por_status' => [
                    'pendente' => SolicitacaoExame::status('pendente')->count(),
                    'em_andamento' => SolicitacaoExame::status('em_andamento')->count(),
                    'concluido' => SolicitacaoExame::status('concluido')->count(),
                    'cancelado' => SolicitacaoExame::status('cancelado')->count(),
                ],
                'por_categoria' => [
                    'realizavel' => SolicitacaoExame::categoria('realizavel')->count(),
                    'nao_realizavel' => SolicitacaoExame::categoria('nao_realizavel')->count(),
                ],
                'pagas' => SolicitacaoExame::pago(true)->count(),
                'nao_pagas' => SolicitacaoExame::pago(false)->count(),
                'valor_total_pendente' => SolicitacaoExame::pago(false)->sum('valor_total'),
                'valor_total_recebido' => SolicitacaoExame::pago(true)->sum('valor_total'),
                'criadas_hoje' => SolicitacaoExame::whereDate('created_at', today())->count(),
                'criadas_esta_semana' => SolicitacaoExame::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Confirmar exames disponíveis e definir preços
     * PUT /api/solicitacoes-exames/{id}/confirmar
     */
    public function confirmar(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'exames_confirmados'              => 'required|array|min:1',
            'exames_confirmados.*.tipo_exame' => 'nullable|string',
            'exames_confirmados.*.disponivel' => 'required|boolean',
            'exames_confirmados.*.preco'      => 'nullable|numeric|min:0',
            'observacoes'                     => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Normalizar tipo_exame vazio para null
        $examesConfirmados = collect($request->exames_confirmados)->map(function ($exame) {
            if (isset($exame['tipo_exame']) && trim($exame['tipo_exame']) === '') {
                $exame['tipo_exame'] = null;
            }
            return $exame;
        })->toArray();

        try {
            // O $id pode ser da tabela exames (consultation-service) ou solicitacoes_exames
            // Tentamos primeiro na tabela exames
            $exameRow = DB::table('exames')->where('id', $id)->first();

            $total = 0;
            foreach ($examesConfirmados as $exame) {
                if ($exame['disponivel'] && isset($exame['preco'])) {
                    $total += $exame['preco'];
                }
            }

            if ($exameRow) {
                // Actualizar status na tabela exames
                DB::table('exames')->where('id', $id)->update([
                    'status'     => 'agendado',
                    'updated_at' => now(),
                ]);

                // Criar/actualizar registo em solicitacoes_exames para o workflow de pagamento
                $solicitacao = SolicitacaoExame::updateOrCreate(
                    ['consulta_id' => $exameRow->consulta_id, 'paciente_nid' => $exameRow->nid],
                    [
                        'consulta_id'        => $exameRow->consulta_id,
                        'paciente_id'        => $exameRow->paciente_id,
                        'nid'                => $exameRow->nid,
                        'paciente_nid'       => $exameRow->nid,
                        'solicitante_id'     => $exameRow->medico_solicitante_id,
                        'medico_nome'        => $exameRow->medico_solicitante_nome,
                        'exames_solicitados' => [$exameRow],
                        'exames'             => $examesConfirmados,
                        'data_solicitacao'   => $exameRow->data_solicitacao ?? now(),
                        'status'             => 'confirmada',
                        'valor_total'        => $total,
                        'data_confirmacao'   => now(),
                        'observacoes'        => $request->observacoes,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => "Exames confirmados. Total a pagar: " . number_format($total, 2) . " MZN",
                    'data'    => [
                        'solicitacao_id' => $solicitacao->id,
                        'exame_id'       => $exameRow->id,
                        'total'          => $total,
                        'status'         => 'confirmada'
                    ]
                ]);
            }

            // Fallback: procurar em solicitacoes_exames
            $solicitacao = SolicitacaoExame::findOrFail($id);
            $solicitacao->update([
                'status'           => 'confirmada',
                'exames'           => $examesConfirmados,
                'valor_total'      => $total,
                'data_confirmacao' => now(),
                'observacoes'      => $request->observacoes ?? $solicitacao->observacoes,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Exames confirmados. Total a pagar: " . number_format($total, 2) . " MZN",
                'data'    => [
                    'solicitacao_id' => $solicitacao->id,
                    'total'          => $total,
                    'status'         => 'confirmada'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao confirmar exames',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processar pagamento dos exames
     * POST /api/solicitacoes-exames/{id}/processar-pagamento
     */
    public function processarPagamento(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'valor_pago'           => 'required|numeric|min:0',
            'metodo_pagamento'     => 'required|in:dinheiro,cartao,transferencia,seguro',
            'referencia_pagamento' => 'nullable|string|max:100',
            'observacoes'          => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Procurar na tabela solicitacoes_exames (pode ter sido criado via confirmar)
            $solicitacao = SolicitacaoExame::find($id);

            if (!$solicitacao) {
                // Fallback: confirmar() cria a solicitacao com consulta_id=exame.consulta_id
                // Tentar encontrar pela consulta_id se o id é da tabela exames
                $exameRow = DB::table('exames')->where('id', $id)->first();
                if ($exameRow) {
                    $solicitacao = SolicitacaoExame::where('consulta_id', $exameRow->consulta_id)->first();
                }
            }

            if (!$solicitacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitação não encontrada. Confirme os exames primeiro.'
                ], 404);
            }

            if ($solicitacao->status !== 'confirmada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Exames devem ser confirmados antes do pagamento'
                ], 400);
            }

            $solicitacao->update([
                'status'               => 'paga',
                'valor_pago'           => $request->valor_pago,
                'metodo_pagamento'     => $request->metodo_pagamento,
                'referencia_pagamento' => $request->referencia_pagamento,
                'data_pagamento'       => now(),
                'observacoes'          => $request->observacoes ?? $solicitacao->observacoes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento registado com sucesso',
                'data'    => [
                    'solicitacao_id' => $solicitacao->id,
                    'status'         => 'paga',
                    'recibo'         => $request->referencia_pagamento
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeitar exames (recepção recusa realizar os exames)
     * POST /api/solicitacoes-exames/{id}/rejeitar
     */
    public function rejeitar(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Motivo de rejeição é obrigatório',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Actualizar na tabela exames
            $exameRow = DB::table('exames')->where('id', $id)->first();
            if ($exameRow) {
                DB::table('exames')->where('id', $id)->update([
                    'status'     => 'cancelado',
                    'updated_at' => now(),
                ]);

                // Actualizar também em solicitacoes_exames se existir
                SolicitacaoExame::where('consulta_id', $exameRow->consulta_id)
                    ->whereNotIn('status', ['cancelada', 'concluida'])
                    ->update([
                        'status'               => 'cancelada',
                        'motivo_cancelamento'  => $request->motivo,
                        'data_cancelamento'    => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Exame rejeitado com sucesso',
                    'data'    => ['exame_id' => $id, 'status' => 'cancelado']
                ]);
            }

            // Fallback: solicitacoes_exames
            $solicitacao = SolicitacaoExame::findOrFail($id);

            if (in_array($solicitacao->status, ['paga', 'em_laboratorio', 'concluida'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível rejeitar uma solicitação já paga ou em processamento'
                ], 400);
            }

            $solicitacao->update([
                'status'              => 'cancelada',
                'motivo_cancelamento' => $request->motivo,
                'data_cancelamento'   => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitação rejeitada com sucesso',
                'data'    => ['solicitacao_id' => $solicitacao->id, 'status' => 'cancelada']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao rejeitar exame',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agendar colheita no laboratório
     * POST /api/solicitacoes-exames/{id}/agendar-colheita
     */
    public function agendarColheita(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data_colheita' => 'required|date|after:now',
            'hora_colheita' => 'required|string|max:10',
            'observacoes'   => 'nullable|string|max:1000',
            'tecnico_id'    => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $solicitacao = SolicitacaoExame::findOrFail($id);

            if ($solicitacao->status !== 'paga') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento deve ser registado antes de agendar colheita'
                ], 400);
            }

            // Chamar laboratory-service para criar o agendamento
            $labServiceUrl = env('LABORATORY_SERVICE_URL', 'http://127.0.0.1:8003');
            $serviceToken = env('SERVICE_TOKEN', 'shared-secret-token');

            $paciente = Paciente::find($solicitacao->paciente_id);
            
            $exames = [];
            foreach ($solicitacao->exames_confirmados ?? [] as $exame) {
                if ($exame['disponivel'] ?? false) {
                    $exames[] = [
                        'tipo_exame' => $exame['tipo_exame'],
                        'prioridade' => $exame['prioridade'] ?? 'normal',
                    ];
                }
            }

            try {
                $response = Http::withHeaders([
                    'X-Service-Token' => $serviceToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])->post("{$labServiceUrl}/api/laboratorio/agendamentos", [
                    'solicitacao_exame_id' => $solicitacao->id,
                    'consulta_id' => $solicitacao->consulta_id,
                    'paciente_id' => $solicitacao->paciente_id,
                    'nid' => $paciente->nid ?? null,
                    'nome' => $paciente->nome_completo ?? null,
                    'data_colheita' => $request->data_colheita,
                    'hora_colheita' => $request->hora_colheita,
                    'observacoes' => $request->observacoes,
                    'tecnico_id' => $request->tecnico_id,
                    'exames' => $exames
                ]);

                if ($response->successful()) {
                    $labData = $response->json();
                    $agendamentoColheitaId = $labData['data']['id'] ?? null;

                    $solicitacao->update([
                        'status' => 'agendada',
                        'data_colheita' => $request->data_colheita,
                        'hora_colheita' => $request->hora_colheita,
                        'agendamento_colheita_id' => $agendamentoColheitaId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Colheita agendada para ' . date('d/m/Y', strtotime($request->data_colheita)) . ' às ' . $request->hora_colheita,
                        'data' => [
                            'solicitacao_id' => $solicitacao->id,
                            'agendamento_colheita_id' => $agendamentoColheitaId,
                            'status' => 'agendada',
                            'data_colheita' => $request->data_colheita
                        ]
                    ]);
                } else {
                    Log::error('Falha ao criar agendamento no laboratory-service', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao comunicar com o laboratório'
                    ], 500);
                }

            } catch (\Exception $httpError) {
                Log::error('Erro HTTP ao chamar laboratory-service', [
                    'error' => $httpError->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao comunicar com o laboratório',
                    'error' => $httpError->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao agendar colheita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar solicitação de exames
     * POST /api/solicitacoes-exames/{id}/cancelar
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $solicitacao = SolicitacaoExame::findOrFail($id);

            $solicitacao->update([
                'status' => 'cancelada',
                'motivo_cancelamento' => $request->motivo,
                'data_cancelamento' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitação cancelada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar solicitação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}