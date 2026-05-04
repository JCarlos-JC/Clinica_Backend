<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Appointment Service
|--------------------------------------------------------------------------
*/

// Health check endpoint (sem autenticação)
Route::get('/health', function () {
    return response()->json([
        'service' => 'appointment-service',
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
            'service' => 'appointment-service',
            'message' => 'Autenticação funcionando!',
            'user' => $request->attributes->get('user_data')
        ]);
    });

    // Adicione suas rotas de appointments aqui
    // Exemplo:
    // Route::apiResource('appointments', AppointmentController::class);
    
});
