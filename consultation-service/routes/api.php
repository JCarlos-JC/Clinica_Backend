<?php

use App\Http\Controllers\Api\ConsultaController;
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\PrescricaoController;
use App\Http\Controllers\Api\ExameController;
use App\Http\Controllers\Api\TransferenciaController;
use App\Http\Controllers\Api\AltaController;
use App\Http\Controllers\Api\ObitoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Consultation Service
|--------------------------------------------------------------------------
*/

// Rotas públicas (sem autenticação)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'consultation-service',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Rotas disponíveis para o frontend (com CORS configurado)
Route::middleware(['api'])->group(function () {
    
    // ============================================
    // ROTAS DE AGENDA (Gestão de Agendamentos)
    // ============================================
    Route::prefix('agenda')->group(function () {
        // Listar agendamentos do triage-service
        Route::get('/agendamentos', [AgendaController::class, 'listarAgendamentos']);
        Route::get('/pendentes', [AgendaController::class, 'listarPendentes']); //Lista dados do triage-service puros
        Route::get('/agendamentos/{id}', [AgendaController::class, 'buscarAgendamento']); //Lista dados do triage-service
        
        // Criar agendamento (chamado pelo Patient Service após pagamento)
        Route::post('/agendamentos', [AgendaController::class, 'criarAgendamento']);
        
        // Aceitar/Recusar agendamentos
        Route::post('/agendamentos/{id}/aceitar', [AgendaController::class, 'aceitarAgendamento']); //fall
        Route::post('/agendamentos/aceitar-multiplos', [AgendaController::class, 'aceitarMultiplos']); //fall
        Route::post('/agendamentos/{id}/recusar', [AgendaController::class, 'recusarAgendamento']); //fall
        
        // Consultas criadas a partir de agendamentos
        Route::get('/consultas-agendadas', [AgendaController::class, 'consultasAgendadas']); //fall
        Route::post('/consultas/{id}/sincronizar', [AgendaController::class, 'sincronizarStatus']); //fall
        
        // Rotas de transferência (compatibilidade com frontend)
        Route::post('/consultas-agendadas/{id}/transferir-medico', [ConsultaController::class, 'transferirMedico']);
        Route::post('/consultas-agendadas/{id}/transferir-especialidade', [ConsultaController::class, 'transferirEspecialidade']);
    });
    
    // ============================================
    // ROTAS DE CONSULTAS
    // ============================================
    
    // Rota para receber agendamentos da triagem (webhook/integração)
    Route::post('/consultas/receber-agendamento', [ConsultaController::class, 'receberAgendamento']); //fall
    
    // Rota para finalizar consulta com prescrições e exames
    Route::post('/consultas/finalizar', [ConsultaController::class, 'finalizar']);
    
    // Rotas de compatibilidade com frontend
    Route::get('/medicos', [ConsultaController::class, 'listarMedicos']);
    Route::get('/especialidades', [ConsultaController::class, 'listarEspecialidades']);
    
    // Rotas de consulta
    Route::prefix('consultas')->group(function () {
        // Listagens
        Route::get('/', [ConsultaController::class, 'index']);
        Route::get('/agendadas', [ConsultaController::class, 'getAgendadas']);
        Route::get('/hoje', [ConsultaController::class, 'getConsultasHoje']);
        Route::get('/pendentes', [ConsultaController::class, 'getPendentes']);
        Route::get('/realizadas', [ConsultaController::class, 'realizadas']);
        Route::get('/retorno-exames', [ConsultaController::class, 'getRetornoExames']);
        Route::get('/estatisticas', [ConsultaController::class, 'estatisticas']);
        Route::get('/transferidos-especialidade', [ConsultaController::class, 'listarTransferidosEspecialidade']);
        
        // Notificação de resultados de laboratório (chamado pelo laboratory-service)
        Route::post('/retorno-exames/notificar', [ConsultaController::class, 'receberResultadosLab']);
        
        // Buscar médicos por especialidade
        Route::get('/medicos-por-especialidade', [ConsultaController::class, 'getMedicosPorEspecialidade']);
        Route::post('/buscar-medicos-especialidade', [ConsultaController::class, 'buscarMedicosPorEspecialidade']);
        
        // Busca por paciente
        Route::get('/paciente/{pacienteId}', [ConsultaController::class, 'getByPaciente']);
        Route::get('/nid/{nid}', [ConsultaController::class, 'getByNid']);
        
        // Busca por médico
        Route::get('/medico/{medicoId}', [ConsultaController::class, 'getByMedico']);
        
        // Detalhes da consulta (rotas específicas ANTES das genéricas)
        Route::get('/{id}/medicos-disponiveis', [ConsultaController::class, 'getMedicosDisponiveisParaTransferencia']);
        Route::get('/{id}/historico', [ConsultaController::class, 'getHistorico']);
        Route::get('/{id}/anexos', [ConsultaController::class, 'getAnexos']);
        Route::get('/{id}/exames', [ConsultaController::class, 'getExames']);
        Route::get('/{id}', [ConsultaController::class, 'show']);
        
        // Ações sobre consulta
        Route::post('/{id}/iniciar', [ConsultaController::class, 'iniciarAtendimento']);
        Route::put('/{id}/atualizar', [ConsultaController::class, 'atualizarConsulta']);
        Route::post('/{id}/finalizar', [ConsultaController::class, 'finalizarConsulta']);
        Route::post('/{id}/cancelar', [ConsultaController::class, 'cancelarConsulta']);
        Route::post('/{id}/transferir-medico', [ConsultaController::class, 'transferirMedico']);
        Route::post('/{id}/transferir-especialidade', [ConsultaController::class, 'transferirEspecialidade']);
        Route::post('/{id}/anexos', [ConsultaController::class, 'uploadAnexo']);
        Route::post('/{id}/exames', [ConsultaController::class, 'solicitarExames']);
        Route::post('/{id}/exames/solicitar', [ConsultaController::class, 'solicitarExames']);
        Route::post('/{id}/alta', [AltaController::class, 'registrarAlta']);
        Route::post('/{id}/obito', [ObitoController::class, 'registrarObito']);
    });

    // ============================================
    // ROTAS DE PRESCRIÇÕES
    // ============================================
    // Rotas aninhadas em consultas
    Route::prefix('consultas/{consultaId}')->group(function () {
        Route::get('/prescricoes', [PrescricaoController::class, 'porConsulta']);
        Route::post('/prescricoes', [PrescricaoController::class, 'store']);
        Route::put('/prescricoes/{prescricaoId}', [PrescricaoController::class, 'update']);
        Route::delete('/prescricoes/{prescricaoId}', [PrescricaoController::class, 'destroy']);
    });
    
    Route::prefix('prescricoes')->group(function () {
        // CRUD
        Route::get('/', [PrescricaoController::class, 'index']);
        Route::get('/{id}', [PrescricaoController::class, 'show']);
        Route::post('/', [PrescricaoController::class, 'store']);
        Route::put('/{id}', [PrescricaoController::class, 'update']);
        Route::delete('/{id}', [PrescricaoController::class, 'destroy']);
        
        // Ações específicas
        Route::post('/{id}/dispensar', [PrescricaoController::class, 'dispensar']);
        Route::post('/{id}/cancelar', [PrescricaoController::class, 'cancelar']);
        Route::post('/{id}/substituir', [PrescricaoController::class, 'substituir']);
        
        // Listagens especiais
        Route::get('/consulta/{consultaId}', [PrescricaoController::class, 'porConsulta']);
        Route::get('/paciente/{nid}/ativas', [PrescricaoController::class, 'ativasPorPaciente']);
        Route::get('/controladas/pendentes', [PrescricaoController::class, 'controladasPendentes']);
    });

    // ============================================
    // ROTAS DE EXAMES
    // ============================================
    Route::prefix('exames')->group(function () {
        // CRUD
        Route::get('/', [ExameController::class, 'index']);
        Route::get('/pendentes', [ExameController::class, 'pendentes']);
        Route::get('/urgentes', [ExameController::class, 'urgentes']);
        Route::get('/paciente/{pacienteId}', [ExameController::class, 'porPaciente']);
        Route::get('/consulta/{consultaId}', [ExameController::class, 'porConsulta']);
        Route::get('/{id}', [ExameController::class, 'show']);
        Route::post('/', [ExameController::class, 'store']);
        Route::put('/{id}', [ExameController::class, 'update']);
        Route::delete('/{id}', [ExameController::class, 'destroy']);
        
        // Workflow do exame
        Route::post('/{id}/agendar', [ExameController::class, 'agendar']);
        Route::post('/{id}/coletar', [ExameController::class, 'registrarColeta']);
        Route::post('/{id}/resultado', [ExameController::class, 'registrarResultado']);
        Route::post('/{id}/laudo', [ExameController::class, 'registrarLaudo']);
        Route::post('/{id}/anexo', [ExameController::class, 'adicionarAnexo']);
        Route::post('/{id}/cancelar', [ExameController::class, 'cancelar']);
        
        // Listagens especiais (manter compatibilidade)
        Route::get('/urgentes/listar', [ExameController::class, 'urgentes']);
        Route::get('/pendentes-laudo/listar', [ExameController::class, 'pendentesLaudo']);
    });

    // ============================================
    // ROTAS DE TRANSFERÊNCIAS
    // ============================================
    Route::prefix('transferencias')->group(function () {
        // CRUD
        Route::get('/', [TransferenciaController::class, 'index']);
        
        // Listagens especiais (devem vir ANTES das rotas parametrizadas)
        Route::get('/pendentes', [TransferenciaController::class, 'pendentes']);
        Route::get('/solicitadas', [TransferenciaController::class, 'solicitadas']);
        Route::get('/urgentes', [TransferenciaController::class, 'urgentes']);
        Route::get('/aguardando-transporte', [TransferenciaController::class, 'aguardandoTransporte']);
        Route::get('/consulta/{consultaId}', [TransferenciaController::class, 'porConsulta']);
        
        // Rotas parametrizadas (devem vir DEPOIS)
        Route::get('/{id}', [TransferenciaController::class, 'show']);
        Route::post('/', [TransferenciaController::class, 'store']);
        Route::put('/{id}', [TransferenciaController::class, 'update']);
        Route::delete('/{id}', [TransferenciaController::class, 'destroy']);
        
        // Ações específicas
        Route::post('/{id}/aceitar', [TransferenciaController::class, 'aceitar']);
        Route::post('/{id}/recusar', [TransferenciaController::class, 'recusar']);
        Route::post('/{id}/iniciar-transporte', [TransferenciaController::class, 'iniciarTransporte']);
        Route::post('/{id}/concluir', [TransferenciaController::class, 'concluir']);
        Route::post('/{id}/cancelar', [TransferenciaController::class, 'cancelar']);
    });

    // ============================================
    // ROTAS DE ALTAS
    // ============================================
    Route::prefix('altas')->group(function () {
        // CRUD
        Route::get('/', [AltaController::class, 'index']);
        Route::get('/{id}', [AltaController::class, 'show']);
        Route::post('/', [AltaController::class, 'store']);
        Route::put('/{id}', [AltaController::class, 'update']);
        Route::delete('/{id}', [AltaController::class, 'destroy']);
        
        // Ações específicas
        Route::post('/{id}/finalizar-documentacao', [AltaController::class, 'finalizarDocumentacao']);
        Route::post('/{id}/adicionar-pendencia', [AltaController::class, 'adicionarPendencia']);
        Route::get('/{id}/gerar-relatorio', [AltaController::class, 'gerarRelatorio']);
        
        // Listagens especiais
        Route::get('/consulta/{consultaId}', [AltaController::class, 'porConsulta']);
        Route::get('/melhoradas/listar', [AltaController::class, 'melhoradas']);
        Route::get('/curadas/listar', [AltaController::class, 'curadas']);
        Route::get('/necessitam-retorno/listar', [AltaController::class, 'necessitamRetorno']);
        Route::get('/pendentes/listar', [AltaController::class, 'pendentes']);
    });

    // ============================================
    // ROTAS DE ÓBITOS
    // ============================================
    Route::prefix('obitos')->group(function () {
        // CRUD
        Route::get('/', [ObitoController::class, 'index']);
        Route::get('/{id}', [ObitoController::class, 'show']);
        Route::post('/', [ObitoController::class, 'store']);
        Route::put('/{id}', [ObitoController::class, 'update']);
        Route::delete('/{id}', [ObitoController::class, 'destroy']);
        
        // Ações específicas
        Route::post('/{id}/registrar-declaracao', [ObitoController::class, 'registrarDeclaracao']);
        Route::post('/{id}/liberar-corpo', [ObitoController::class, 'liberarCorpo']);
        Route::post('/{id}/registrar-sepultamento', [ObitoController::class, 'registrarSepultamento']);
        Route::post('/{id}/vincular-funeraria', [ObitoController::class, 'vincularFuneraria']);
        Route::post('/{id}/registrar-cartorio', [ObitoController::class, 'registrarCartorio']);
        
        // Listagens especiais
        Route::get('/consulta/{consultaId}', [ObitoController::class, 'porConsulta']);
        Route::get('/naturais/listar', [ObitoController::class, 'naturais']);
        Route::get('/violentos/listar', [ObitoController::class, 'violentos']);
        Route::get('/aguardando-declaracao/listar', [ObitoController::class, 'aguardandoDeclaracao']);
        Route::get('/aguardando-liberacao/listar', [ObitoController::class, 'aguardandoLiberacao']);
        Route::get('/com-necropsia/listar', [ObitoController::class, 'comNecropsia']);
    });
});

