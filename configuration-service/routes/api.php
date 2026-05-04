<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RacaController;
use App\Http\Controllers\Api\ProvinciaController;
use App\Http\Controllers\Api\DistritoController;
use App\Http\Controllers\Api\BairroController;
use App\Http\Controllers\Api\FormaMedicamentoController;
use App\Http\Controllers\Api\ViaAdministracaoController;
use App\Http\Controllers\Api\MedicamentoController;
use App\Http\Controllers\Api\TipoDocumentoController;
use App\Http\Controllers\Api\TipoUtenteController;
use App\Http\Controllers\Api\GrauParentescoController;
use App\Http\Controllers\Api\UnidadeOrganicaController;
use App\Http\Controllers\Api\MetodoPagamentoController;
use App\Http\Controllers\Api\EspecialidadeController;
use App\Http\Controllers\Api\TipoConsultaController;
use App\Http\Controllers\Api\PrecoConsultaController;
use App\Http\Controllers\Api\PrecoEspecialidadeController;
use App\Http\Controllers\Api\FuncaoEspecialidadeController;
use App\Http\Controllers\Api\EstadoConsultaController;
use App\Http\Controllers\Api\ClassificacaoRiscoController;
use App\Http\Controllers\Api\EstadoUrgenciaController;
use App\Http\Controllers\Api\TipoExameController;
use App\Http\Controllers\Api\ExameController;




// // Adicionar este bloco no final do arquivo, antes da rota de health
// // Rotas públicas para testes e demonstração
// Route::prefix('public')->group(function () {
//     // Raças
//     Route::get('/racas', [RacaController::class, 'index']);
//     Route::get('/racas/{id}', [RacaController::class, 'show']);
    
//     // Localização
//     Route::get('/provincias', [ProvinciaController::class, 'index']);
//     Route::get('/provincias/{id}', [ProvinciaController::class, 'show']);
//     Route::get('/provincias/{id}/distritos', [ProvinciaController::class, 'getDistritos']);
//     Route::get('/distritos', [DistritoController::class, 'index']);
//     Route::get('/distritos/{id}', [DistritoController::class, 'show']);
//     Route::get('/distritos/{id}/bairros', [DistritoController::class, 'getBairros']);
//     Route::get('/bairros', [BairroController::class, 'index']);
//     Route::get('/bairros/{id}', [BairroController::class, 'show']);
    
//     // Medicamentos
//     Route::get('/formas-medicamento', [FormaMedicamentoController::class, 'index']);
//     Route::get('/vias-administracao', [ViaAdministracaoController::class, 'index']);
//     Route::get('/medicamentos', [MedicamentoController::class, 'index']);
//     Route::get('/medicamentos/{id}', [MedicamentoController::class, 'show']);
    
//     // Outros recursos públicos que você desejar expor
// });


// Rota para verificar cabeçalhos de requisição
Route::get('/headers-test', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'Headers recebidos',
        'authorization_header' => $request->header('Authorization'),
        'authorization_header_raw' => $request->headers->get('Authorization'),
        'bearer_token' => $request->bearerToken(),
        'all_headers' => $request->headers->all(),
        'server_variables' => [
            'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'não definido'
        ]
    ]);
});



// Adicionar no final do arquivo, antes da rota de health
Route::get('/auth-test', function (Request $request) {
    try {
        // Verificar se o token está sendo recebido
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nenhum token Bearer encontrado no cabeçalho Authorization',
                'headers' => $request->header()
            ], 401);
        }

        // Tentar autenticar com o token
        $user = auth('api')->user();
        if ($user) {
            return response()->json([
                'status' => 'success',
                'message' => 'Token autenticado com sucesso',
                'user' => $user
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido ou usuário não encontrado',
                'token_info' => [
                    'token_exists' => !empty($token),
                    'token_length' => strlen($token)
                ]
            ], 401);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erro ao processar o token',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});









// Rotas para comunicação entre microserviços (restritas por token de serviço)
Route::prefix('services')->group(function () {
    // Raças
    Route::get('/racas', [RacaController::class, 'index']);
    Route::get('/racas/{id}', [RacaController::class, 'show']);
    
    // Localização
    Route::get('/provincias', [ProvinciaController::class, 'index']);
    Route::get('/provincias/{id}', [ProvinciaController::class, 'show']);
    Route::get('/provincias/{id}/distritos', [ProvinciaController::class, 'getDistritos']);
    Route::get('/distritos', [DistritoController::class, 'index']);
    Route::get('/distritos/{id}', [DistritoController::class, 'show']);
    Route::get('/distritos/{id}/bairros', [DistritoController::class, 'getBairros']);
    Route::get('/bairros', [BairroController::class, 'index']);
    Route::get('/bairros/{id}', [BairroController::class, 'show']);
    
    // Medicamentos
    Route::get('/formas-medicamento', [FormaMedicamentoController::class, 'index']);
    Route::get('/vias-administracao', [ViaAdministracaoController::class, 'index']);
    Route::get('/medicamentos', [MedicamentoController::class, 'index']);
    Route::get('/medicamentos/{id}', [MedicamentoController::class, 'show']);
    
    // Documentos e Tipos de Utente
    Route::get('/tipos-documento', [TipoDocumentoController::class, 'index']);
    Route::get('/tipos-utente', [TipoUtenteController::class, 'index']);
    Route::get('/tipos-utentes/{id}/consultas-disponiveis', [TipoUtenteController::class, 'getConsultasDisponiveis']);
    Route::get('/graus-parentesco', [GrauParentescoController::class, 'index']);
    
    // Organização
    Route::get('/unidades-organica', [UnidadeOrganicaController::class, 'index']);
    
    // Pagamentos
    Route::get('/metodos-pagamento', [MetodoPagamentoController::class, 'index']);
    
    // Consultas e Especialidades
    Route::get('/especialidades', [EspecialidadeController::class, 'index']);
    Route::get('/tipos-consulta', [TipoConsultaController::class, 'index']);
    Route::get('/funcoes-especialidade', [FuncaoEspecialidadeController::class, 'index']);
    Route::get('/estados-consulta', [EstadoConsultaController::class, 'index']);
    Route::get('tipos-consultas/{id}/valor', [TipoConsultaController::class, 'getValor']);
    
    // Preços de Especialidades (CRUD completo para microserviços)
    Route::get('/precos-especialidades', [PrecoEspecialidadeController::class, 'index']);
    Route::get('/precos-especialidades/preco', [PrecoEspecialidadeController::class, 'getPreco']);
    Route::get('/precos-especialidades/{id}', [PrecoEspecialidadeController::class, 'show']);
    Route::post('/precos-especialidades', [PrecoEspecialidadeController::class, 'store']);
    Route::put('/precos-especialidades/{id}', [PrecoEspecialidadeController::class, 'update']);
    Route::patch('/precos-especialidades/{id}', [PrecoEspecialidadeController::class, 'update']);
    Route::delete('/precos-especialidades/{id}', [PrecoEspecialidadeController::class, 'destroy']);
    
    // Triagem e Urgência
    Route::get('/classificacoes-risco', [ClassificacaoRiscoController::class, 'index']);
    Route::get('/estados-urgencia', [EstadoUrgenciaController::class, 'index']);
    
    // Exames
    Route::get('/tipos-exame', [TipoExameController::class, 'index']);
    Route::get('/tipos-exame/categoria/{categoria}', [TipoExameController::class, 'getByCategoria']);
});

// Rotas para a API pública (restritas por autenticação de usuário via JWT do authentication-service)
Route::middleware([\App\Http\Middleware\MicroserviceAuth::class])->group(function () {
    // Raças
    Route::apiResource('/racas', RacaController::class);
    
    // Localização
    Route::apiResource('/provincias', ProvinciaController::class);
    Route::get('/provincias/{id}/distritos', [ProvinciaController::class, 'getDistritos']);
    Route::apiResource('/distritos', DistritoController::class);
    Route::get('/distritos/{id}/bairros', [DistritoController::class, 'getBairros']);
    Route::apiResource('/bairros', BairroController::class);
    
    // Medicamentos
    Route::apiResource('/formas-medicamento', FormaMedicamentoController::class);
    Route::apiResource('/vias-administracao', ViaAdministracaoController::class);
    Route::apiResource('/medicamentos', MedicamentoController::class);
    
    // Documentos e Tipos de Utente (singular e plural para compatibilidade)
    Route::apiResource('/tipos-documento', TipoDocumentoController::class);
    Route::get('/tipos-documentos', [TipoDocumentoController::class, 'index']); // Alias plural
    Route::get('/tipos-documentos/{id}', [TipoDocumentoController::class, 'show']); // Alias plural
    
    Route::apiResource('/tipos-utente', TipoUtenteController::class);
    Route::get('/tipos-utentes', [TipoUtenteController::class, 'index']); // Alias plural
    Route::get('/tipos-utentes/{id}', [TipoUtenteController::class, 'show']); // Alias plural
    
    Route::apiResource('/graus-parentesco', GrauParentescoController::class);
    
    // Organização (singular e plural para compatibilidade)
    Route::apiResource('/unidades-organica', UnidadeOrganicaController::class);
    Route::get('/unidades-organicas', [UnidadeOrganicaController::class, 'index']); // Alias plural
    Route::get('/unidades-organicas/{id}', [UnidadeOrganicaController::class, 'show']); // Alias plural
    
    // Pagamentos
    Route::apiResource('/metodos-pagamento', MetodoPagamentoController::class);
    
    // Consultas e Especialidades
    Route::apiResource('/especialidades', EspecialidadeController::class);
    Route::apiResource('/tipos-consulta', TipoConsultaController::class);
    Route::get('/tipos-consultas', [TipoConsultaController::class, 'index']); // Alias plural
    Route::get('/tipos-consultas/{id}', [TipoConsultaController::class, 'show']); // Alias plural
    
    // Preços de Consultas
    Route::apiResource('/precos-consultas', PrecoConsultaController::class);
    Route::get('/precos-consultas/preco', [PrecoConsultaController::class, 'getPreco']); // Obter preço específico
    
    // Preços de Especialidades
    Route::get('/precos-especialidades/preco', [PrecoEspecialidadeController::class, 'getPreco']); // ANTES do apiResource
    Route::apiResource('/precos-especialidades', PrecoEspecialidadeController::class);
    
    Route::apiResource('/funcoes-especialidade', FuncaoEspecialidadeController::class);
    Route::apiResource('/estados-consulta', EstadoConsultaController::class);
    
    // Triagem e Urgência
    Route::apiResource('/classificacoes-risco', ClassificacaoRiscoController::class);
    Route::apiResource('/estados-urgencia', EstadoUrgenciaController::class);
    
    // Exames
    Route::apiResource('/tipos-exame', TipoExameController::class);
    Route::get('/tipos-exame/categoria/{categoria}', [TipoExameController::class, 'getByCategoria']);
    
    // Preços de Exames
    Route::get('/exames/preco', [ExameController::class, 'getPreco']); // ANTES do apiResource
    Route::apiResource('/exames', ExameController::class);
});

// // Rota pública para verificação de saúde do serviço
// Route::get('/health', function() {
//     return response()->json([
//         'status' => 'ok',
//         'service' => 'configuration',
//         'timestamp' => now()->toIso8601String()
//     ]);
// });