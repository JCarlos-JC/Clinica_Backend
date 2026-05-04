<?php
// filepath: services/triage-service/app/Http/Controllers/Api/AgendamentoConsultaController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgendamentoConsulta;
use App\Models\Triagem;
use App\Services\ConsultationServiceClient;
use App\Services\PatientServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AgendamentoConsultaController extends Controller
{
    protected $consultationService;
    protected $patientService;
    
    public function __construct(
        ConsultationServiceClient $consultationService,
        PatientServiceClient $patientService
    ) {
        $this->consultationService = $consultationService;
        $this->patientService = $patientService;
    }
    
    /**
     * Schedule consultation after triage
     * Matches: handleAgendarConsulta from TriagemPaciente.jsx
     * POST /api/triagens/agendar-consulta (busca por NID no body)
     * POST /api/triagens/{nid}/agendar-consulta (busca por NID na URL)
     */
    public function agendarConsulta(Request $request, $nidParam = null)
    {
        // Log dos dados recebidos para debug
        Log::info('Dados recebidos para agendamento', [
            'nidParam' => $nidParam,
            'body' => $request->all()
        ]);
        
        $validator = Validator::make($request->all(), [
            'nid' => $nidParam ? 'nullable|string' : 'required|string',
            'medico' => 'required|string',
            'medico_id' => 'nullable|integer',
            'tipo_consulta' => 'required|string', // Aceita nome do tipo de consulta
            'tipo_consulta_id' => 'nullable|integer',
            'especialidade' => 'nullable|string', // Aceita nome da especialidade
            'especialidade_id' => 'nullable|integer',
            'data_consulta' => 'required|date',
            'hora_consulta' => 'required|date_format:H:i',
            'motivo_consulta' => 'required|string',
            'observacoes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            Log::warning('Validação falhou ao agendar consulta', [
                'errors' => $validator->errors()->toArray(),
                'data_received' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Get NID from URL parameter or request body
            $nid = $nidParam ?? $request->nid;
            
            // Find latest completed triage by NID
            $triagem = Triagem::with('sinaisVitais')
                ->where('nid', $nid)
                ->where('status', 'triagem_concluida')
                ->whereNull('consulta_id')
                ->latest('id')
                ->first();
            
            if (!$triagem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Triagem concluída não encontrada para este NID ou consulta já agendada',
                    'nid' => $nid
                ], 404);
            }

            
            // Buscar tipo_consulta_id pelo nome se não fornecido
            $tipoConsultaId = $request->tipo_consulta_id;
            if (!$tipoConsultaId && $request->tipo_consulta) {
                try {
                    $tipoConsulta = DB::table('tipos_consulta')
                        ->where('nome', $request->tipo_consulta)
                        ->orWhere('codigo', $request->tipo_consulta)
                        ->first();
                    
                    if ($tipoConsulta) {
                        $tipoConsultaId = $tipoConsulta->id;
                        Log::info('Tipo de consulta encontrado', [
                            'nome' => $request->tipo_consulta,
                            'id' => $tipoConsultaId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao buscar tipo de consulta: ' . $e->getMessage());
                }
            }
            
            // Buscar especialidade_id pelo nome se fornecido
            $especialidadeId = $request->especialidade_id;
            $especialidadeNome = $request->especialidade ?? 'Clínica Geral';
            if (!$especialidadeId && $especialidadeNome) {
                try {
                    $especialidade = DB::table('especialidades')
                        ->where('nome', $especialidadeNome)
                        ->orWhere('codigo', $especialidadeNome)
                        ->first();
                    
                    if ($especialidade) {
                        $especialidadeId = $especialidade->id;
                        Log::info('Especialidade encontrada', [
                            'nome' => $especialidadeNome,
                            'id' => $especialidadeId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao buscar especialidade: ' . $e->getMessage());
                }
            }
            
            // Map tipo_consulta to priority (baseado nos novos tipos)
            $prioridadeMap = [
                'Emergência' => 'emergencia',
                'Consulta de Emergência' => 'emergencia',
                'Muito Urgente' => 'urgente',
                'Urgente' => 'urgente',
                'Consulta Regular' => 'normal',
                'Consulta de Especialidade' => 'normal',
                'Consulta de Acompanhamento' => 'normal',
                'Retorno com Exames' => 'normal',
                'Não Urgência' => 'normal'
            ];
            
            // Buscar medico_id pelo nome do médico se não fornecido
            $medicoId = $request->medico_id;
            if (!$medicoId && $request->medico) {
                try {
                    // Buscar médico pelo nome no authentication-service database
                    $authDb = DB::connection('mysql')->getPdo();
                    $authDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                    
                    // Extrair palavras do nome (ex: "Dr. Ana Cardoso" -> ["Ana", "Cardoso"])
                    $palavras = array_filter(array_map('trim', explode(' ', $request->medico)), function($p) {
                        return strlen($p) > 2 && !in_array(strtolower($p), ['dr', 'dr.', 'dra', 'dra.', 'de', 'da', 'do']);
                    });
                    
                    // Tentar encontrar médico que contenha alguma das palavras
                    $medico = null;
                    if (!empty($palavras)) {
                        foreach ($palavras as $palavra) {
                            $stmt = $authDb->prepare(
                                "SELECT id, nome FROM usuarios WHERE nome LIKE ? AND cargo LIKE '%édic%' LIMIT 1"
                            );
                            $stmt->execute(['%' . $palavra . '%']);
                            $medico = $stmt->fetch(\PDO::FETCH_ASSOC);
                            if ($medico) break;
                        }
                    }
                    
                    // Fallback: busca exata ou genérica
                    if (!$medico) {
                        $stmt = $authDb->prepare(
                            "SELECT id, nome FROM usuarios WHERE nome = ? OR nome LIKE ? LIMIT 1"
                        );
                        $stmt->execute([$request->medico, '%' . $request->medico . '%']);
                        $medico = $stmt->fetch(\PDO::FETCH_ASSOC);
                    }
                    
                    if ($medico) {
                        $medicoId = $medico['id'];
                        Log::info('Médico encontrado pelo nome', [
                            'nomeRecebido' => $request->medico,
                            'nomeEncontrado' => $medico['nome'],
                            'id' => $medicoId
                        ]);
                    } else {
                        Log::warning('Médico não encontrado pelo nome', [
                            'nome' => $request->medico,
                            'palavras_buscadas' => $palavras
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao buscar médico: ' . $e->getMessage());
                }
            }
            
            // Create scheduling record
            $agendamento = AgendamentoConsulta::create([
                'codigo_agendamento' => AgendamentoConsulta::gerarCodigoAgendamento(),
                'triagem_id' => $triagem->id,
                'paciente_id' => $triagem->paciente_id,
                'nid' => $triagem->nid,
                'nome' => $triagem->nome,
                'apelido' => $triagem->apelido,
                'genero' => $triagem->genero,
                'data_nascimento' => $triagem->data_nascimento,
                'especialidade' => $especialidadeNome,
                'especialidade_id' => $especialidadeId,
                'medico' => $request->medico,
                'medico_id' => $medicoId,
                'tipo_consulta' => $request->tipo_consulta,
                'tipo_consulta_id' => $tipoConsultaId,
                'data_consulta' => $request->data_consulta,
                'hora_consulta' => $request->hora_consulta,
                'motivo_consulta' => $request->motivo_consulta,
                'observacoes' => $request->observacoes,
                'data_agendamento' => now(),
                'status' => 'agendado',
                'prioridade' => $prioridadeMap[$request->tipo_consulta] ?? 'normal',
            ]);
            
            // CRIAR CONSULTA DIRETAMENTE NO CONSULTATION-SERVICE (SEM CONFIRMAÇÃO)
            $consultaId = null;
            $consultaCriada = false;
            
            try {
                Log::info('Criando consulta via ConsultationServiceClient', [
                    'agendamento_id' => $agendamento->id
                ]);
                
                // Criar consulta usando o service client (padrão PatientServiceClient)
                $resultado = $this->consultationService->criarConsulta([
                    'agendamento_id' => $agendamento->id,
                    'triagem_id' => $agendamento->triagem_id,
                    'paciente_id' => $agendamento->paciente_id,
                    'nid' => $agendamento->nid,
                    'medico' => $agendamento->medico,
                    'medico_id' => $agendamento->medico_id,
                    'especialidade' => $agendamento->especialidade,
                    'especialidade_id' => $agendamento->especialidade_id,
                    'tipo_consulta' => $agendamento->tipo_consulta,
                    'tipo_consulta_id' => $agendamento->tipo_consulta_id,
                    'data_consulta' => $agendamento->data_consulta,
                    'hora_consulta' => $agendamento->hora_consulta,
                    'motivo_consulta' => $agendamento->motivo_consulta,
                    'observacoes' => $agendamento->observacoes,
                    'status' => 'agendada',
                    'prioridade' => $agendamento->prioridade,
                ]);
                
                if ($resultado['success'] && $resultado['consulta_id']) {
                    $consultaId = $resultado['consulta_id'];
                    
                    // Atualizar agendamento com consulta_id IMEDIATAMENTE
                    $agendamento->consulta_id = $consultaId;
                    $agendamento->enviado_consultation_service = true;
                    $agendamento->data_envio_consultation_service = now();
                    $agendamento->status = 'confirmada';
                    $agendamento->data_confirmacao = now();
                    $agendamento->save();
                    
                    // Atualizar triagem
                    $triagem->consulta_agendada = true;
                    $triagem->consulta_id = $consultaId;
                    $triagem->save();
                    
                    $consultaCriada = true;
                    
                    Log::info('✅ Consulta criada com sucesso!', [
                        'agendamento_id' => $agendamento->id,
                        'consulta_id' => $consultaId
                    ]);
                } else {
                    Log::error('❌ Falha ao criar consulta', [
                        'agendamento_id' => $agendamento->id,
                        'error' => $resultado['error'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('💥 Erro ao criar consulta', [
                    'agendamento_id' => $agendamento->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => $consultaCriada 
                    ? 'Consulta agendada e criada com sucesso' 
                    : 'Consulta agendada (criação da consulta falhou)',
                'data' => [
                    'triagem' => $triagem->fresh(['sinaisVitais', 'agendamentoConsulta']),
                    'agendamento' => $agendamento->fresh(),
                    'consulta_id' => $consultaId,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao agendar consulta: ' . $e->getMessage(), [
                'nid' => $nidParam ?? $request->nid ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao agendar consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * List all scheduled consultations
     * GET /api/agendamentos
     */
    public function index(Request $request)
    {
        $query = AgendamentoConsulta::with('triagem');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('tipo_consulta')) {
            $query->where('tipo_consulta', $request->tipo_consulta);
        }
        
        if ($request->has('data_consulta')) {
            $query->whereDate('data_consulta', $request->data_consulta);
        }
        
        $perPage = $request->get('per_page', 15);
        $agendamentos = $query->orderBy('data_consulta', 'desc')
                             ->orderBy('hora_consulta', 'asc')
                             ->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $agendamentos
        ]);
    }
    
    /**
     * Get scheduled consultations (agendado)
     * Called by consultation-service to fetch agendamentos with status 'agendado'
     * Only returns agendamentos that haven't been sent to consultation-service yet
     * GET /api/agendamentos/pendentes
     */
    public function getPendentes()
    {
        $agendamentos = AgendamentoConsulta::with('triagem')
                                          ->aguardandoConsulta()
                                          ->where('enviado_consultation_service', false) // Only unsent ones
                                          ->orderBy('prioridade')
                                          ->orderBy('data_consulta', 'asc')
                                          ->orderBy('hora_consulta', 'asc')
                                          ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $agendamentos
        ]);
    }
    
    /**
     * Get pending consultations
     * GET /api/agendamentos/aguardando
     */
    public function aguardando(Request $request)
    {
        $agendamentos = AgendamentoConsulta::with('triagem')
                                           ->aguardandoConsulta()
                                           ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $agendamentos,
            'total' => $agendamentos->count()
        ]);
    }
    
    /**
     * Get today's consultations
     * GET /api/agendamentos/hoje
     */
    public function hoje(Request $request)
    {
        $agendamentos = AgendamentoConsulta::with('triagem')
                                           ->consultasHoje()
                                           ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $agendamentos,
            'total' => $agendamentos->count()
        ]);
    }
    
    /**
     * Get scheduling details
     * GET /api/agendamentos/{id}
     */
    public function show($id)
    {
        try {
            $agendamento = AgendamentoConsulta::with(['triagem.sinaisVitais'])
                                              ->findOrFail($id);
            
            // Adicionar validação de agendamento
            $data = $agendamento->toArray();
            $data['valido'] = $agendamento->isValido();
            $data['requer_pagamento'] = $agendamento->tipo === 'transferencia_especialidade';
            
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Agendamento não encontrado'
            ], 404);
        }
    }
    
    /**
     * Get scheduling by triagem_id
     * GET /api/agendamentos/by-triagem/{triagemId}
     * Para uso pelo consultation-service na transferência de médico
     */
    public function getByTriagemId($triagemId)
    {
        try {
            Log::info('Buscando agendamento por triagem_id', ['triagem_id' => $triagemId]);
            
            $agendamento = AgendamentoConsulta::where('triagem_id', $triagemId)
                                              ->whereNull('deleted_at')
                                              ->first();
            
            if (!$agendamento) {
                Log::warning('Agendamento não encontrado', ['triagem_id' => $triagemId]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Agendamento não encontrado para triagem_id: ' . $triagemId
                ], 404);
            }
            
            Log::info('Agendamento encontrado', [
                'id' => $agendamento->id,
                'triagem_id' => $agendamento->triagem_id,
                'consulta_id' => $agendamento->consulta_id,
                'paciente_id' => $agendamento->paciente_id,
                'medico_id' => $agendamento->medico_id
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $agendamento->id,
                    'consulta_id' => $agendamento->consulta_id,
                    'paciente_id' => $agendamento->paciente_id,
                    'medico_id' => $agendamento->medico_id,
                    'codigo_agendamento' => $agendamento->codigo_agendamento,
                    'triagem_id' => $agendamento->triagem_id,
                    'tipo_consulta_id' => $agendamento->tipo_consulta_id,
                    'especialidade_id' => $agendamento->especialidade_id,
                    'data_consulta' => $agendamento->data_consulta,
                    'hora_consulta' => $agendamento->hora_consulta,
                    'status' => $agendamento->status
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar agendamento por triagem_id: ' . $e->getMessage(), [
                'triagem_id' => $triagemId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar agendamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Confirm consultation
     * PATCH /api/agendamentos/{id}/confirmar
     */
    public function confirmar($id)
    {
        try {
            $agendamento = AgendamentoConsulta::findOrFail($id);
            
            $agendamento->confirmar();
            
            // Notify consultation service
            if ($agendamento->consulta_id) {
                $this->consultationService->atualizarStatusConsulta(
                    $agendamento->consulta_id,
                    'confirmada'
                );
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consulta confirmada com sucesso',
                'data' => $agendamento
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao confirmar consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cancel consultation
     * POST /api/agendamentos/{id}/cancelar
     */
    public function cancelar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string',
            'cancelado_por' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $agendamento = AgendamentoConsulta::findOrFail($id);
            
            if (!$agendamento->pode_cancelar) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta consulta não pode ser cancelada'
                ], 400);
            }
            
            $agendamento->cancelar($request->motivo, $request->cancelado_por);
            
            // Notify consultation service
            if ($agendamento->consulta_id) {
                $this->consultationService->cancelarConsulta(
                    $agendamento->consulta_id,
                    $request->motivo
                );
            }
            
            // Update triage
            if ($agendamento->triagem) {
                $agendamento->triagem->update([
                    'consulta_agendada' => false,
                    'consulta_id' => null,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consulta cancelada com sucesso',
                'data' => $agendamento
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao cancelar consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reschedule consultation
     * PATCH /api/agendamentos/{id}/remarcar
     */
    public function remarcar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'data_consulta' => 'required|date|after_or_equal:today',
            'hora_consulta' => 'required|date_format:H:i',
            'motivo' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $agendamento = AgendamentoConsulta::findOrFail($id);
            
            $agendamento->update([
                'data_consulta' => $request->data_consulta,
                'hora_consulta' => $request->hora_consulta,
                'observacoes' => $agendamento->observacoes . "\nRemarcado: " . ($request->motivo ?? 'Sem motivo especificado'),
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consulta remarcada com sucesso',
                'data' => $agendamento
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao remarcar consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update doctor for an appointment (for doctor transfer)
     * PATCH /api/agendamentos/{id}/medico
     */
    public function atualizarMedico(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'medico_id' => 'required|integer',
            'medico' => 'nullable|string',
            'medico_nome' => 'nullable|string',
            'especialidade' => 'nullable|string',
            'especialidade_id' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $agendamento = AgendamentoConsulta::findOrFail($id);
            
            $medicoAnterior = $agendamento->medico_id;
            $medicoAnteriorNome = $agendamento->medico;
            $especialidadeAnterior = $agendamento->especialidade;
            
            // Atualizar médico
            $agendamento->medico_id = $request->medico_id;
            $medicoNome = $request->medico ?? $request->medico_nome;
            if ($medicoNome) {
                $agendamento->medico = $medicoNome;
            }
            
            // Atualizar especialidade se fornecida
            if ($request->especialidade) {
                $agendamento->especialidade = $request->especialidade;
            }
            if ($request->especialidade_id) {
                $agendamento->especialidade_id = $request->especialidade_id;
            }
            
            // Adicionar observação sobre a transferência
            $observacao = "\n[Transferência] ";
            if ($request->especialidade && $especialidadeAnterior !== $request->especialidade) {
                $observacao .= "Especialidade: {$especialidadeAnterior} → {$request->especialidade}. ";
            }
            $observacao .= "Médico: {$medicoAnteriorNome} (ID: {$medicoAnterior}) → ";
            $observacao .= ($medicoNome ?? 'ID: ' . $request->medico_id);
            $agendamento->observacoes = ($agendamento->observacoes ?? '') . $observacao;
            
            $agendamento->save();
            
            Log::info('Médico/Especialidade do agendamento atualizado via transferência', [
                'agendamento_id' => $agendamento->id,
                'medico_anterior_id' => $medicoAnterior,
                'medico_novo_id' => $request->medico_id,
                'especialidade_anterior' => $especialidadeAnterior,
                'especialidade_nova' => $request->especialidade
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Agendamento atualizado com sucesso',
                'data' => $agendamento
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Agendamento não encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar médico do agendamento', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar médico do agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar status de pagamento e tipo do agendamento
     * PATCH /api/agendamentos/{id}/pagamento
     */
    public function atualizarPagamento(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pendente,confirmada,confirmado,cancelado,em_atendimento,finalizado',
            'status_pagamento' => 'nullable|in:pendente,pago,cancelado',
            'tipo' => 'nullable|in:triagem,transferencia_medico,transferencia_especialidade',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $agendamento = AgendamentoConsulta::findOrFail($id);
            
            // Atualizar campos se fornecidos
            if ($request->has('status')) {
                $agendamento->status = $request->status;
            }
            
            if ($request->has('status_pagamento')) {
                $agendamento->status_pagamento = $request->status_pagamento;
            }
            
            if ($request->has('tipo')) {
                $agendamento->tipo = $request->tipo;
            }
            
            $agendamento->save();
            
            Log::info('Pagamento do agendamento atualizado', [
                'agendamento_id' => $agendamento->id,
                'dados' => $request->only(['status', 'status_pagamento', 'tipo'])
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pagamento atualizado com sucesso',
                'data' => $agendamento
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Agendamento não encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pagamento do agendamento', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar pagamento do agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get scheduled consultations from consultation-service
     * GET /api/agendamentos/consultation-service/agendadas
     */
    public function consultasAgendadasCS(Request $request)
    {
        try {
            $filtros = [];
            
            // Adicionar filtros opcionais
            if ($request->has('paciente_id')) {
                $filtros['paciente_id'] = $request->paciente_id;
            }
            
            if ($request->has('medico_id')) {
                $filtros['medico_id'] = $request->medico_id;
            }
            
            if ($request->has('especialidade')) {
                $filtros['especialidade'] = $request->especialidade;
            }
            
            if ($request->has('data_inicio')) {
                $filtros['data_inicio'] = $request->data_inicio;
            }
            
            if ($request->has('data_fim')) {
                $filtros['data_fim'] = $request->data_fim;
            }
            
            // Buscar consultas agendadas no consultation-service
            $consultas = $this->consultationService->buscarConsultasAgendadas($filtros);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consultas agendadas do consultation-service',
                'data' => $consultas,
                'total' => is_array($consultas) ? count($consultas) : ($consultas['total'] ?? 0)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas agendadas do CS: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas agendadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get today's consultations from consultation-service
     * GET /api/agendamentos/consultation-service/hoje
     */
    public function consultasHojeCS()
    {
        try {
            $consultas = $this->consultationService->buscarConsultasHoje();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consultas de hoje do consultation-service',
                'data' => $consultas,
                'total' => is_array($consultas) ? count($consultas) : ($consultas['total'] ?? 0)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas de hoje do CS: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas de hoje',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get patient consultations from consultation-service
     * GET /api/agendamentos/consultation-service/paciente/{pacienteId}
     */
    public function consultasPorPacienteCS($pacienteId, Request $request)
    {
        try {
            $status = $request->get('status', null);
            
            $consultas = $this->consultationService->buscarConsultasPorPaciente($pacienteId, $status);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consultas do paciente no consultation-service',
                'data' => $consultas
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas do paciente no CS: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas do paciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get consultation details from consultation-service
     * GET /api/agendamentos/consultation-service/consulta/{consultaId}
     */
    public function consultaDetalheCS($consultaId)
    {
        try {
            $consulta = $this->consultationService->buscarConsulta($consultaId);
            
            if (!$consulta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta não encontrada no consultation-service'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $consulta
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar consulta no CS: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get combined view: local + consultation-service scheduled consultations
     * GET /api/agendamentos/completo/agendadas
     */
    public function consultasAgendadasCompleto(Request $request)
    {
        try {
            // Buscar agendamentos locais
            $agendamentosLocais = AgendamentoConsulta::with('triagem')
                ->aguardandoConsulta()
                ->get();
            
            // Buscar consultas do consultation-service
            $consultasCS = $this->consultationService->buscarConsultasAgendadas();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Consultas agendadas (local + consultation-service)',
                'data' => [
                    'locais' => $agendamentosLocais,
                    'consultation_service' => $consultasCS,
                ],
                'total' => [
                    'locais' => $agendamentosLocais->count(),
                    'consultation_service' => is_array($consultasCS) ? count($consultasCS) : ($consultasCS['total'] ?? 0),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas agendadas completo: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas agendadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available medicos from authentication-service
     * GET /api/options/medicos
     */
    public function getMedicos()
    {
        try {
            // Buscar m�dicos do banco de dados
            $authDb = DB::connection('mysql')->getPdo();
            $stmt = $authDb->prepare(
                "SELECT id, nome, email, cargo 
                 FROM usuarios 
                 WHERE cargo LIKE '%edic%' OR cargo LIKE '%doctor%' OR cargo LIKE '%Médic%'
                 ORDER BY nome ASC"
            );
            $stmt->execute();
            $medicos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return response()->json([
                'status' => 'success',
                'data' => $medicos,
                'total' => count($medicos)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar m�dicos: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar lista de m�dicos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available especialidades from medicos cargo
     * GET /api/options/especialidades
     */
    public function getEspecialidades()
    {
        try {
            // Buscar médicos do authentication-service database
            $authDb = DB::connection('mysql')->getPdo();
            $authDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $stmt = $authDb->prepare("
                SELECT DISTINCT cargo 
                FROM usuarios 
                WHERE (cargo LIKE '%edic%' OR cargo LIKE '%doctor%' OR cargo LIKE '%Médic%')
                AND cargo IS NOT NULL
                ORDER BY cargo ASC
            ");
            $stmt->execute();
            $medicos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Extrair especialidades dos cargos
            $especialidades = [];
            $especialidadesUnicas = [];
            
            foreach ($medicos as $medico) {
                $cargo = $medico['cargo'];
                
                // Remover prefixos comuns: Médico, Médica, Dr., Dra., etc
                $especialidade = preg_replace('/^(Dr\.?|Dra\.?|Doutor|Doutora|Médico|Médica)\s+/i', '', $cargo);
                
                // Limpar espaços
                $especialidade = trim($especialidade);
                
                // Adicionar se não for vazio e não existir ainda
                $especialidadeKey = mb_strtolower($especialidade);
                if (!empty($especialidade) && !isset($especialidadesUnicas[$especialidadeKey])) {
                    $especialidadesUnicas[$especialidadeKey] = true;
                    $especialidades[] = [
                        'id' => count($especialidades) + 1,
                        'nome' => $especialidade,
                        'cargo_original' => $cargo
                    ];
                }
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $especialidades,
                'total' => count($especialidades)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar especialidades dos médicos: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar especialidades',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available tipos de consulta from database
     * GET /api/options/tipos-consulta
     */
    public function getTiposConsulta()
    {
        try {
            // Buscar tipos de consulta da tabela tipos_consulta
            $tiposConsulta = DB::table('tipos_consulta')
                ->select('*')
                ->get()
                ->toArray();
            
            return response()->json([
                'status' => 'success',
                'data' => $tiposConsulta,
                'total' => count($tiposConsulta)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar tipos de consulta: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar tipos de consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza o status do agendamento e vincula a consulta_id
     * PUT /api/agendamentos/{id}/status
     */
    public function atualizarStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:agendado,confirmada,em_atendimento,concluida,cancelada,paciente_faltou',
            'consulta_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $agendamento = AgendamentoConsulta::findOrFail($id);

            // Atualizar status
            $agendamento->status = $request->status;

            // Atualizar consulta_id se fornecido
            if ($request->has('consulta_id')) {
                $agendamento->consulta_id = $request->consulta_id;
                $agendamento->enviado_consultation_service = true;
                $agendamento->data_envio_consultation_service = now();
            }

            // Atualizar data de confirmação se status for confirmada
            if ($request->status === 'confirmada' && !$agendamento->data_confirmacao) {
                $agendamento->data_confirmacao = now();
            }

            $agendamento->save();

            Log::info('Status do agendamento atualizado', [
                'agendamento_id' => $id,
                'status' => $request->status,
                'consulta_id' => $request->consulta_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Status atualizado com sucesso',
                'data' => $agendamento
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status do agendamento', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marca agendamento como sincronizado com consultation-service
     * POST /api/agendamentos/{id}/marcar-sincronizado
     */
    public function marcarSincronizado(Request $request, $id)
    {
        try {
            $agendamento = AgendamentoConsulta::findOrFail($id);

            $agendamento->enviado_consultation_service = true;
            $agendamento->data_envio_consultation_service = now();
            $agendamento->save();

            Log::info('Agendamento marcado como sincronizado', [
                'agendamento_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Agendamento marcado como sincronizado',
                'data' => $agendamento
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao marcar agendamento como sincronizado', [
                'agendamento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao marcar como sincronizado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Corrigir agendamentos sem consulta_id (criar consultas retroativamente)
     * POST /api/agendamentos/corrigir-consultas
     */
    public function corrigirConsultas(Request $request)
    {
        try {
            // Buscar agendamentos sem consulta_id
            $agendamentosSemConsulta = AgendamentoConsulta::whereNull('consulta_id')
                ->where('status', '!=', 'cancelada')
                ->get();

            $total = $agendamentosSemConsulta->count();
            
            if ($total === 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Todos os agendamentos já têm consulta_id',
                    'corrigidos' => 0,
                    'total' => 0
                ]);
            }

            $corrigidos = 0;
            $erros = [];
            $consultationServiceUrl = env('CONSULTATION_SERVICE_URL', 'http://127.0.0.1:8007');

            Log::info("Iniciando correção de $total agendamentos sem consulta_id");

            foreach ($agendamentosSemConsulta as $agendamento) {
                try {
                    // Chamar rota de aceitar agendamento
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => $request->header('Authorization') ?? '',
                    ])->post("{$consultationServiceUrl}/api/agenda/agendamentos/{$agendamento->id}/aceitar");

                    if ($response->successful()) {
                        $responseData = $response->json();
                        $consultaId = $responseData['consulta']['id'] ?? null;

                        if ($consultaId) {
                            // Atualizar agendamento
                            $agendamento->consulta_id = $consultaId;
                            $agendamento->enviado_consultation_service = true;
                            $agendamento->data_envio_consultation_service = now();
                            $agendamento->status = 'confirmada';
                            $agendamento->data_confirmacao = now();
                            $agendamento->save();

                            // Atualizar triagem se existir
                            if ($agendamento->triagem) {
                                $agendamento->triagem->consulta_agendada = true;
                                $agendamento->triagem->consulta_id = $consultaId;
                                $agendamento->triagem->save();
                            }

                            $corrigidos++;
                            
                            Log::info("✅ Agendamento {$agendamento->id} corrigido - consulta_id: $consultaId");
                        }
                    } else {
                        $erros[] = [
                            'agendamento_id' => $agendamento->id,
                            'erro' => 'Status: ' . $response->status() . ' - ' . $response->body()
                        ];
                        Log::warning("❌ Falha ao corrigir agendamento {$agendamento->id}: " . $response->body());
                    }
                } catch (\Exception $e) {
                    $erros[] = [
                        'agendamento_id' => $agendamento->id,
                        'erro' => $e->getMessage()
                    ];
                    Log::error("💥 Erro ao corrigir agendamento {$agendamento->id}: " . $e->getMessage());
                }

                // Pequeno delay para não sobrecarregar
                usleep(100000); // 0.1 segundo
            }

            return response()->json([
                'status' => 'success',
                'message' => "Correção concluída: $corrigidos de $total agendamentos corrigidos",
                'total_agendamentos' => $total,
                'corrigidos' => $corrigidos,
                'falhas' => count($erros),
                'erros' => $erros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro geral ao corrigir consultas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao corrigir consultas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
