<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagamentoConsulta;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controller simplificado para testes do sistema de pagamentos
 */
class PagamentoConsultaTestController extends Controller
{
    /**
     * Dashboard básico para testes
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_pagamentos' => PagamentoConsulta::count(),
                'total_hoje' => PagamentoConsulta::whereDate('created_at', today())->count(),
                'por_status' => PagamentoConsulta::select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->get(),
                'exemplo_paciente' => Paciente::first()?->nid,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Sistema de pagamentos funcionando!',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro no dashboard',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Criar pagamento de teste
     */
    public function criarTeste(): JsonResponse
    {
        try {
            $paciente = Paciente::first();
            
            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum paciente encontrado para teste'
                ], 404);
            }

            $pagamento = PagamentoConsulta::create([
                'paciente_id' => $paciente->id,
                'paciente_nid' => $paciente->nid,
                'tipo_consulta_id' => 1,
                'metodo_pagamento_id' => 1,
                'valor_original' => 500.00,
                'desconto' => 0,
                'valor_pago' => 500.00,
                'status' => 'pago',
                'tipo_pagamento' => 'consulta_regular',
                'data_pagamento' => now(),
                'observacoes' => 'Pagamento de teste criado via API',
            ]);

            // Gerar recibo
            $pagamento->update([
                'numero_recibo' => PagamentoConsulta::gerarNumeroRecibo()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento de teste criado com sucesso!',
                'data' => [
                    'pagamento_id' => $pagamento->id,
                    'numero_recibo' => $pagamento->numero_recibo,
                    'paciente_nid' => $pagamento->paciente_nid,
                    'valor_pago' => $pagamento->valor_pago_formatado,
                    'data_pagamento' => $pagamento->data_pagamento->format('d/m/Y H:i'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pagamento de teste',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar todos pagamentos
     */
    public function listar(): JsonResponse
    {
        try {
            $pagamentos = PagamentoConsulta::with(['paciente'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function($pagamento) {
                    return [
                        'id' => $pagamento->id,
                        'recibo' => $pagamento->numero_recibo,
                        'paciente_nid' => $pagamento->paciente_nid,
                        'paciente_nome' => $pagamento->paciente?->nome,
                        'valor_pago' => $pagamento->valor_pago_formatado,
                        'status' => $pagamento->status,
                        'tipo' => $pagamento->tipo_pagamento,
                        'data' => $pagamento->data_pagamento?->format('d/m/Y H:i'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pagamentos,
                'total' => PagamentoConsulta::count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pagamentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}