<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\PermissionsController;

/*
|--------------------------------------------------------------------------
| Authentication Service API Routes
|--------------------------------------------------------------------------
|
| Here is where you register the API routes for your Authentication Service.
| This microservice handles user authentication, user management and role
| management.
|
*/

// Authentication endpoints
Route::prefix('auth')->group(function () {
    // Rotas públicas
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    
    // Rotas protegidas que precisam de autenticação (usar a classe do middleware diretamente para evitar resolução de alias)
    Route::group(['middleware' => [\App\Http\Middleware\JwtMiddleware::class]], function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('api.auth.change-password');
        Route::get('logs', [AuthController::class, 'getMyAuthLogs'])->name('api.auth.logs');
    });
});

// Service-to-service authentication
Route::post('service-auth', [AuthController::class, 'serviceAuth'])->name('api.service.auth');

// Rota de saúde do sistema que não requer autenticação
Route::get('health', [AuthController::class, 'systemStatus']);

// Rota para diagnóstico de headers (debug)
Route::any('diagnostic/headers', [App\Http\Controllers\Api\DiagnosticController::class, 'showHeaders']);

// Rota para diagnóstico de tokens
Route::post('diagnostic/token', [App\Http\Controllers\Api\DiagnosticController::class, 'analyzeToken']);

// Rota para validação de tokens entre microserviços
Route::prefix('token')->group(function () {
    Route::post('validate', [App\Http\Controllers\Api\TokenValidationController::class, 'validateToken'])
        ->name('api.token.validate');
});

// Rotas para gerenciamento de usuários (protegidas por autenticação e políticas)
Route::group(['middleware' => 'auth:api', 'prefix' => 'users'], function () {
    Route::get('/', [UserController::class, 'index'])->name('api.users.index');
    Route::post('/', [UserController::class, 'store'])->name('api.users.store');
    Route::get('/{id}', [UserController::class, 'show'])->name('api.users.show');
    Route::put('/{id}', [UserController::class, 'update'])->name('api.users.update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('api.users.destroy');
    Route::get('/{id}/logs', [UserController::class, 'authLogs'])->name('api.users.logs');
    
    // Rotas para gerenciamento de roles do usuário
    Route::get('/{id}/roles', [UserController::class, 'getRoles'])->name('api.users.roles');
    Route::post('/{id}/roles', [UserController::class, 'assignRole'])->name('api.users.assign-role');
    Route::delete('/{id}/roles/{roleId}', [UserController::class, 'removeRole'])->name('api.users.remove-role');
});

// Rotas para gerenciamento de roles
Route::group(['middleware' => 'auth:api', 'prefix' => 'roles'], function () {
    Route::get('/', [RolesController::class, 'index'])->name('api.roles.index');
    Route::post('/', [RolesController::class, 'store'])->name('api.roles.store');
    Route::get('/{id}', [RolesController::class, 'show'])->name('api.roles.show');
    Route::put('/{id}', [RolesController::class, 'update'])->name('api.roles.update');
    Route::delete('/{id}', [RolesController::class, 'destroy'])->name('api.roles.destroy');
});

// Rotas para listar permissões
// Route::group(['middleware' => 'auth:api', 'prefix' => 'permissions'], function () {
//     Route::get('/', [PermissionsController::class, 'index'])->name('api.permissions.index');
// });