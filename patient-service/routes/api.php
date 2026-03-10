<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\ParenteController;
use App\Http\Controllers\Api\UtenteAutonomoController;
use App\Http\Controllers\Api\HistoricoController;
use App\Http\Controllers\Api\SolicitacaoExameController;
use App\Http\Controllers\Api\SolicitacaoTriagemController;
use App\Http\Controllers\Api\PagamentoEspecialidadeController;
use App\Models\Paciente;

/*
|--------------------------------------------------------------------------
| API Routes - Patient Service
|--------------------------------------------------------------------------
*/

// Service-to-service routes (SEM autenticação JWT, apenas para microserviços)
Route::prefix('services')->group(function () {
    Route::patch('solicitacoes-triagem/{id}/status', [SolicitacaoTriagemController::class, 'atualizarStatus']);
    
    // Pagamentos de Especialidade (para microserviços)
    Route::post('pagamento-especialidade', [PagamentoEspecialidadeController::class, 'registrarPagamento']);
    Route::get('pagamentos-especialidade', [PagamentoEspecialidadeController::class, 'index']);
});

// Alias para compatibilidade (rota com nome incorreto usado pelo frontend)
Route::prefix('servico')->group(function () {
    Route::get('transferencias-especialidade', [PacienteController::class, 'listarTransferidosEspecialidade']);
});

Route::middleware([\App\Http\Middleware\MicroserviceAuth::class])->group(function () {
    
    // Pacientes Routes
    Route::prefix('pacientes')->group(function () {
        // Configuration endpoints (DEVEM VIR ANTES DAS ROTAS COM {id})
        Route::get('tipos-utentes', [PacienteController::class, 'getTiposUtentes']);
        Route::get('provincias', [PacienteController::class, 'getProvincias']);
        Route::get('distritos', [PacienteController::class, 'getDistritos']);
        Route::get('bairros', [PacienteController::class, 'getBairros']);
        Route::get('tipos-documentos', [PacienteController::class, 'getTiposDocumentos']);
        Route::get('racas', [PacienteController::class, 'getRacas']);
        Route::get('unidades-organicas', [PacienteController::class, 'getUnidadesOrganicas']);
        Route::get('graus-parentesco', [PacienteController::class, 'getGrausParentesco']);
        Route::get('metodos-pagamento', [PacienteController::class, 'getMetodosPagamento']);
        Route::get('tipos-consulta', [PacienteController::class, 'getTiposConsulta']);
        Route::get('valor-consulta', [PacienteController::class, 'getValorConsulta']);
        Route::get('verificar-preco-disponivel', [PacienteController::class, 'verificarPrecoDisponivel']);
        
        // Statistics
        Route::get('statistics', [PacienteController::class, 'statistics']);
        Route::get('estatisticas', [PacienteController::class, 'estatisticas']);
        
        // Next NID
        Route::get('next-nid', [PacienteController::class, 'nextNID']);
        
        // Buscar por NID
        Route::get('find-by-nid', [PacienteController::class, 'findByNID']);
        
        // Parentes por NID (formato: pacientes/{numero}/{ano}/parentes)
        Route::get('{numero}/{ano}/parentes', [PacienteController::class, 'getParentesByNidParts']);
        
        // Dados completos e históricos por NID (formato: numero/ano)
        Route::get('nid/{numero}/{ano}/dados-completos', [PacienteController::class, 'getDadosCompletosByNid']);
        Route::get('nid/{numero}/{ano}/triagens', [PacienteController::class, 'getHistoricoTriagensByNid']);
        Route::get('nid/{numero}/{ano}/sinais-vitais', [PacienteController::class, 'getHistoricoTriagensByNid']); // Sinais vitais vêm das triagens
        Route::get('nid/{numero}/{ano}/exames', [PacienteController::class, 'getHistoricoExamesByNid']);
        Route::post('nid/{numero}/{ano}/exames-solicitados', [PacienteController::class, 'receberExamesSolicitados']);
        Route::get('nid/{numero}/{ano}/consultas', [PacienteController::class, 'getHistoricoConsultasByNid']);
        Route::get('nid/{numero}/{ano}/historico-consultas', [PacienteController::class, 'getHistoricoConsultasByNid']); // Alias
        
        // Resource routes
        Route::get('/', [PacienteController::class, 'index']);
        Route::post('/', [PacienteController::class, 'store']);
        
        // Rotas específicas ANTES das rotas com {id}
        Route::get('dados-contato/{id}', [PacienteController::class, 'getDadosContato']);
        Route::get('transferidos-especialidade', [PacienteController::class, 'listarTransferidosEspecialidade']);
        
        // Pagamentos de Especialidade
        Route::post('pagamento-especialidade', [PagamentoEspecialidadeController::class, 'registrarPagamento']);
        Route::get('pagamentos-especialidade', [PagamentoEspecialidadeController::class, 'index']);
        Route::get('pagamentos-especialidade/consulta/{consultaId}', [PagamentoEspecialidadeController::class, 'buscarPorConsulta']);
        Route::get('pagamentos-especialidade/{id}', [PagamentoEspecialidadeController::class, 'show']);
        Route::get('{id}/pagamentos-especialidade', [PagamentoEspecialidadeController::class, 'listarPorPaciente']);
        
        Route::get('{id}', [PacienteController::class, 'show']);
        Route::put('{id}', [PacienteController::class, 'update']);
        Route::patch('{id}', [PacienteController::class, 'update']);
        Route::delete('{id}', [PacienteController::class, 'destroy']);
        
        // Additional actions
        Route::post('{id}/restore', [PacienteController::class, 'restore']);
        Route::patch('{id}/status', [PacienteController::class, 'changeStatus']);
        
        // Rotas específicas para NIDs (formato: numero/ano)
        Route::get('nid/{numero}/{ano}/dados-pagamento', [PacienteController::class, 'getDadosPagamentoByNid']);
        Route::post('nid/{numero}/{ano}/pagar-consulta', [PacienteController::class, 'pagarConsultaRegularByNid']);
        Route::post('nid/{numero}/{ano}/marcar-triagem', [PacienteController::class, 'marcarTriagemByNid']);
        
        // Rotas para ID numérico ou NID codificado
        Route::get('{id}/dados-pagamento', [PacienteController::class, 'getDadosPagamento']);
        Route::post('{id}/pagar-consulta', [PacienteController::class, 'pagarConsultaRegular']);
        Route::post('{id}/pagar-consulta-regular', [PacienteController::class, 'pagarConsultaRegular']); // Alias para compatibilidade
        Route::post('{id}/marcar-triagem', [PacienteController::class, 'marcarTriagem']);
        
        // 💰 PAGAMENTO - SISTEMA NOVO (salva na tabela pagamentos_consultas)
        Route::post('processar-pagamento', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'processarPagamento']);
        
        // Pagamento de transferência de especialidade
        Route::post('pagamento-especialidade', [PacienteController::class, 'processarPagamentoEspecialidade']);
        
        // Rota legado (para compatibilidade - salva na tabela pacientes)
        Route::post('processar-pagamento-legado', [PacienteController::class, 'processarPagamentoGlobal']);
    });

    // Parentes Routes
    Route::prefix('parentes')->group(function () {
        // Get by paciente
        Route::get('paciente/{pacienteId}', [ParenteController::class, 'byPaciente']);
        
        // Resource routes
        Route::get('/', [ParenteController::class, 'index']);
        Route::post('/', [ParenteController::class, 'store']);
        Route::get('{id}', [ParenteController::class, 'show']);
        Route::put('{id}', [ParenteController::class, 'update']);
        Route::patch('{id}', [ParenteController::class, 'update']);
        Route::delete('{id}', [ParenteController::class, 'destroy']);
    });

    // Utentes Autônomos Routes
    Route::prefix('utentes-autonomos')->group(function () {
        // Statistics
        Route::get('statistics', [UtenteAutonomoController::class, 'statistics']);
        
        // Next NID
        Route::get('next-nid', [UtenteAutonomoController::class, 'nextNID']);
        
        // Resource routes
        Route::get('/', [UtenteAutonomoController::class, 'index']);
        Route::post('/', [UtenteAutonomoController::class, 'store']);
        Route::get('{id}', [UtenteAutonomoController::class, 'show']);
        Route::put('{id}', [UtenteAutonomoController::class, 'update']);
        Route::patch('{id}', [UtenteAutonomoController::class, 'update']);
        Route::delete('{id}', [UtenteAutonomoController::class, 'destroy']);
        
        // Additional actions
        Route::post('{id}/restore', [UtenteAutonomoController::class, 'restore']);
        Route::patch('{id}/status', [UtenteAutonomoController::class, 'changeStatus']);
    });



    // Solicitações de Triagem Routes
    Route::prefix('solicitacoes-triagem')->group(function () {
        // Statistics
        Route::get('statistics', [SolicitacaoTriagemController::class, 'statistics']);
        
        // Get by paciente
        Route::get('paciente/{pacienteId}', [SolicitacaoTriagemController::class, 'byPaciente']);
        
        // Get pending
        Route::get('pendentes', [SolicitacaoTriagemController::class, 'getPendentes']);
        
        // Route::get('concluidas', [SolicitacaoTriagemController::class, 'getConcluidas']);        
        // Resource routes
        Route::get('/', [SolicitacaoTriagemController::class, 'index']);
        Route::post('/', [SolicitacaoTriagemController::class, 'store']);
        Route::get('{id}', [SolicitacaoTriagemController::class, 'show']);
        Route::put('{id}', [SolicitacaoTriagemController::class, 'update']);
        Route::patch('{id}', [SolicitacaoTriagemController::class, 'update']);
        Route::delete('{id}', [SolicitacaoTriagemController::class, 'destroy']);
        
        // Additional actions
        Route::post('{id}/restore', [SolicitacaoTriagemController::class, 'restore']);
        Route::patch('{id}/status', [SolicitacaoTriagemController::class, 'changeStatus']);
        Route::post('{id}/cancelar', [SolicitacaoTriagemController::class, 'cancelar']);
        Route::post('{id}/atender', [SolicitacaoTriagemController::class, 'marcarComoAtendido']);
    });

    // Solicitações de Exame Routes
    Route::prefix('solicitacoes-exames')->group(function () {
        // Statistics
        Route::get('statistics', [SolicitacaoExameController::class, 'statistics']);
        
        // Get by paciente
        Route::get('paciente/{pacienteId}', [SolicitacaoExameController::class, 'byPaciente']);
        
        // Resource routes
        Route::get('/', [SolicitacaoExameController::class, 'index']);
        Route::post('/', [SolicitacaoExameController::class, 'store']);
        Route::get('{id}', [SolicitacaoExameController::class, 'show']);
        Route::put('{id}', [SolicitacaoExameController::class, 'update']);
        Route::patch('{id}', [SolicitacaoExameController::class, 'update']);
        Route::delete('{id}', [SolicitacaoExameController::class, 'destroy']);
        
        // Workflow de exames (conforme documentação)
        Route::put('{id}/confirmar', [SolicitacaoExameController::class, 'confirmar']);
        Route::post('{id}/rejeitar', [SolicitacaoExameController::class, 'rejeitar']);
        Route::post('{id}/processar-pagamento', [SolicitacaoExameController::class, 'processarPagamento']);
        Route::post('{id}/agendar-colheita', [SolicitacaoExameController::class, 'agendarColheita']);
        Route::post('{id}/cancelar', [SolicitacaoExameController::class, 'cancelar']);
        
        // Additional actions (manter compatibilidade)
        Route::post('{id}/restore', [SolicitacaoExameController::class, 'restore']);
        Route::patch('{id}/status', [SolicitacaoExameController::class, 'changeStatus']);
        Route::post('{id}/mark-paid', [SolicitacaoExameController::class, 'markAsPaid']);
    });

    // Pagamentos de Consultas Routes - Configuração de Dados
    Route::prefix('pagamentos-consultas')->group(function () {
        // Dashboard e relatórios
        Route::get('/', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'index']);
        Route::get('dashboard', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'dashboard']);
        Route::get('relatorio-financeiro', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'relatorioFinanceiro']);
        
        // 💰 CONFIGURAÇÃO DE PAGAMENTO
        Route::get('configuracao-pagamento', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'configuracaoPagamento']);
        Route::post('processar-pagamento', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'processarPagamento']);
        Route::get('metodos-pagamento', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'metodosPagamento']);
        Route::post('teste-processar', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'testeProcessar']);
        
        // Operações específicas
        Route::get('{id}', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'show']);
        Route::post('{id}/cancelar', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'cancelar']);
        Route::post('{id}/aplicar-desconto', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'aplicarDesconto']);
        
        // Verificação temporária
        Route::get('verificar-tabela', function() {
            $count = \App\Models\PagamentoConsulta::count();
            $ultimo = \App\Models\PagamentoConsulta::latest()->first();
            return response()->json([
                'total_pagamentos' => $count,
                'ultimo_pagamento' => $ultimo ? [
                    'id' => $ultimo->id,
                    'recibo' => $ultimo->numero_recibo,
                    'valor' => $ultimo->valor_pago,
                    'status' => $ultimo->status,
                    'paciente_nid' => $ultimo->paciente_nid,
                    'created_at' => $ultimo->created_at
                ] : null
            ]);
        });
    });

    // Histórico de pagamentos por paciente
    Route::get('pacientes/{pacienteId}/pagamentos', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'historicoPaciente']);
    Route::get('pacientes/{pacienteId}/retorno-disponivel', [\App\Http\Controllers\Api\PagamentoConsultaController::class, 'verificarRetorno']);


});

// Health check (sem autenticação)
Route::get('/health', function () {
    return response()->json([
        'service' => 'patient-service',
        'status' => 'online',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Verificação temporária (sem autenticação)
Route::get('/verificar-pagamentos', function() {
    $count = \App\Models\PagamentoConsulta::count();
    $ultimo = \App\Models\PagamentoConsulta::latest()->first();
    return response()->json([
        'tabela' => 'pagamentos_consultas',
        'total_pagamentos' => $count,
        'ultimo_pagamento' => $ultimo ? [
            'id' => $ultimo->id,
            'recibo' => $ultimo->numero_recibo,
            'valor_formatado' => $ultimo->valor_pago_formatado,
            'status' => $ultimo->status,
            'paciente_nid' => $ultimo->paciente_nid,
            'observacoes' => $ultimo->observacoes,
            'created_at' => $ultimo->created_at->format('d/m/Y H:i:s')
        ] : null
    ]);
});
