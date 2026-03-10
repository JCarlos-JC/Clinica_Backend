<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LabAgendamentoController;
use App\Http\Controllers\Api\LabColheitaController;

/*
|--------------------------------------------------------------------------
| API Routes - Laboratory Service
|--------------------------------------------------------------------------
*/

// Health check endpoint (sem autenticação)
Route::get('/health', function () {
    return response()->json([
        'service' => 'laboratory-service',
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
});

// Rotas protegidas com autenticação JWT
Route::middleware([\App\Http\Middleware\MicroserviceAuth::class])->group(function () {
    
    // Endpoint de teste
    Route::get('/test', function (Request $request) {
        return response()->json([
            'success' => true,
            'service' => 'laboratory-service',
            'message' => 'Autenticação funcionando!',
            'user' => $request->attributes->get('user_data')
        ]);
    });

    // ============================================
    // ROTAS DE AGENDAMENTOS DE COLHEITA
    // ============================================
    Route::prefix('laboratorio')->group(function () {
        
        // Agendamentos de Colheita
        Route::prefix('agendamentos')->group(function () {
            Route::get('/', [LabAgendamentoController::class, 'index']);
            Route::post('/', [LabAgendamentoController::class, 'store']); // chamado pelo patient-service
            Route::get('/pendentes', [LabAgendamentoController::class, 'pendentes']);
            Route::get('/{id}', [LabAgendamentoController::class, 'show']);
        });

        // Colheitas (execução e resultados)
        Route::prefix('colheitas')->group(function () {
            Route::post('/{id}/iniciar', [LabColheitaController::class, 'iniciar']);
            Route::post('/{id}/concluir', [LabColheitaController::class, 'concluir']);
            Route::post('/{id}/anexo', [LabColheitaController::class, 'adicionarAnexo']);
            Route::post('/{id}/cancelar', [LabColheitaController::class, 'cancelar']);
        });
    });    
});