<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Allow root-level access to some API endpoints for local/dev clients
// that call the service without the `/api` prefix. These routes mirror
// the API routes for `solicitacoes-triagem` and apply the same
// `MicroserviceAuth` middleware so behavior is consistent.
use App\Http\Controllers\Api\SolicitacaoTriagemController;

Route::middleware([\App\Http\Middleware\MicroserviceAuth::class])->group(function () {
    Route::prefix('solicitacoes-triagem')->group(function () {
        Route::get('/', [SolicitacaoTriagemController::class, 'index']);
        Route::post('/', [SolicitacaoTriagemController::class, 'store']);
        Route::get('{id}', [SolicitacaoTriagemController::class, 'show']);
        Route::put('{id}', [SolicitacaoTriagemController::class, 'update']);
        Route::patch('{id}', [SolicitacaoTriagemController::class, 'update']);
        Route::delete('{id}', [SolicitacaoTriagemController::class, 'destroy']);
    });
});
