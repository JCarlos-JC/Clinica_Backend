<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TriagemController;
use App\Http\Controllers\Api\AgendamentoConsultaController;
use App\Http\Controllers\Api\SinaisVitaisController;

/*
|--------------------------------------------------------------------------
| API Routes - Triage Service
|--------------------------------------------------------------------------
*/

// Health check endpoint (sem autenticação)
Route::get('/health', function () {
    return response()->json([
        'service' => 'triage-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});

// Rotas públicas para consulta interna entre microserviços
Route::get('/agendamentos/{id}/detalhes', [AgendamentoConsultaController::class, 'show']);

// Rota de manutenção (sem autenticação para facilitar correção)
Route::post('/agendamentos/corrigir-consultas', [AgendamentoConsultaController::class, 'corrigirConsultas']);

// Rotas protegidas com autenticação JWT do microserviço
Route::middleware([\App\Http\Middleware\MicroserviceAuth::class])->group(function () {
    
    // Options routes - dados para formulários do frontend
    Route::prefix('options')->group(function () {
        Route::get('/medicos', [AgendamentoConsultaController::class, 'getMedicos']);
        Route::get('/especialidades', [AgendamentoConsultaController::class, 'getEspecialidades']);
        Route::get('/tipos-consulta', [AgendamentoConsultaController::class, 'getTiposConsulta']);
    });
    
    // Triagem routes
    Route::prefix('triagens')->group(function () {
        Route::get('/', [TriagemController::class, 'index']);
        Route::post('/', [TriagemController::class, 'store']);
        Route::get('/pendentes', [TriagemController::class, 'pendentes']);
        Route::get('/concluidas', [TriagemController::class, 'concluidas']);
        Route::get('/estatisticas', [TriagemController::class, 'estatisticas']);
        
        // Specific routes with {id} MUST come before generic {id} routes
        Route::post('/{id}/realizar', [TriagemController::class, 'realizarTriagem']);
        Route::patch('/{id}/status', [TriagemController::class, 'updateStatus']);
        
        // Agendar consulta pode usar NID (na URL ou no body)
        Route::post('/agendar-consulta', [AgendamentoConsultaController::class, 'agendarConsulta']);
        Route::post('/{nid}/agendar-consulta', [AgendamentoConsultaController::class, 'agendarConsulta'])->where('nid', '.*');
        
        // Generic {id} routes come last
        Route::get('/{id}', [TriagemController::class, 'show']);
        Route::put('/{id}', [TriagemController::class, 'update']);
        Route::delete('/{id}', [TriagemController::class, 'destroy']);
    });
    
    // Agendamentos de Consultas
    Route::prefix('agendamentos')->group(function () {
        Route::get('/', [AgendamentoConsultaController::class, 'index']);
        Route::get('/pendentes', [AgendamentoConsultaController::class, 'getPendentes']); // NEW: For consultation-service
        Route::get('/aguardando', [AgendamentoConsultaController::class, 'aguardando']);
        Route::get('/hoje', [AgendamentoConsultaController::class, 'hoje']);
        
        // Rotas para buscar consultas do consultation-service
        Route::get('/consultation-service/agendadas', [AgendamentoConsultaController::class, 'consultasAgendadasCS']);
        Route::get('/consultation-service/hoje', [AgendamentoConsultaController::class, 'consultasHojeCS']);
        Route::get('/consultation-service/paciente/{pacienteId}', [AgendamentoConsultaController::class, 'consultasPorPacienteCS']);
        Route::get('/consultation-service/consulta/{consultaId}', [AgendamentoConsultaController::class, 'consultaDetalheCS']);
        
        // Rota combinada (local + consultation-service)
        Route::get('/completo/agendadas', [AgendamentoConsultaController::class, 'consultasAgendadasCompleto']);
        
        // NEW: Buscar agendamento por triagem_id (para transferência de médico)
        Route::get('/by-triagem/{id}', [AgendamentoConsultaController::class, 'getByTriagemId']);
        
        Route::get('/{id}', [AgendamentoConsultaController::class, 'show']);
        Route::patch('/{id}/confirmar', [AgendamentoConsultaController::class, 'confirmar']);
        Route::post('/{id}/cancelar', [AgendamentoConsultaController::class, 'cancelar']);
        Route::patch('/{id}/remarcar', [AgendamentoConsultaController::class, 'remarcar']);
        
        // NEW: Rotas para sincronização com consultation-service
        Route::put('/{id}/status', [AgendamentoConsultaController::class, 'atualizarStatus']);
        Route::post('/{id}/marcar-sincronizado', [AgendamentoConsultaController::class, 'marcarSincronizado']);
        
        // Atualizar médico do agendamento (para transferências)
        Route::patch('/{id}/medico', [AgendamentoConsultaController::class, 'atualizarMedico']);
        
        // Atualizar pagamento do agendamento (para transferências de especialidade)
        Route::patch('/{id}/pagamento', [AgendamentoConsultaController::class, 'atualizarPagamento']);
    });

    // Sinais Vitais
    Route::prefix('sinais-vitais')->group(function () {
        Route::get('/', [SinaisVitaisController::class, 'index']);
        Route::post('/', [SinaisVitaisController::class, 'store']);
        Route::get('/estatisticas', [SinaisVitaisController::class, 'estatisticas']);
        Route::get('/criticos', [SinaisVitaisController::class, 'criticos']);
        Route::post('/calcular-imc', [SinaisVitaisController::class, 'calcularIMC']);
        
        // Buscar histórico de sinais vitais por NID
        Route::get('/historico/{nid}', [SinaisVitaisController::class, 'getHistoricoByNid'])->where('nid', '.*');
        
        Route::get('/triagem/{triagemId}', [SinaisVitaisController::class, 'getByTriagem']);
        Route::put('/triagem/{triagemId}', [SinaisVitaisController::class, 'updateByTriagem']);
        Route::get('/{id}', [SinaisVitaisController::class, 'show']);
        Route::put('/{id}', [SinaisVitaisController::class, 'update']);
        Route::delete('/{id}', [SinaisVitaisController::class, 'destroy']);
    });
});