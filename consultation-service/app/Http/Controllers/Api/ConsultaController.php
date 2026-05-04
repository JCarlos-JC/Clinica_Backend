<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinalizarConsultaRequest;
use App\Models\Consulta;
use App\Models\Prescricao;
use App\Models\Exame;
use App\Services\TriageServiceClient;
use App\Services\PatientServiceClient;
use App\Services\LaboratoryServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConsultaController extends Controller
{
    protected $triageService;
    protected $patientService;
    protected $laboratoryService;

    public function __construct(
        TriageServiceClient $triageService,
        PatientServiceClient $patientService,
        LaboratoryServiceClient $laboratoryService
    ) {
        $this->triageService = $triageService;
        $this->patientService = $patientService;
        $this->laboratoryService = $laboratoryService;
    }

    /**
     * Recebe agendamento da triagem e cria consulta
     * POST /api/consultas/receber-agendamento
     */
    public function receberAgendamento(Request $request)
    {
        Log::info('Recebendo agendamento da triagem', $request->all());

        $validator = Validator::make($request->all(), [
            'agendamento_id' => 'required|integer',
            'nid' => 'required|string',
            'paciente_id' => 'nullable|integer',
            'triagem_id' => 'nullable|integer',
            'medico' => 'required|string',
            'medico_id' => 'nullable|integer',
            'tipo_consulta' => 'required|string',
            'tipo_consulta_id' => 'nullable|integer',
            'especialidade' => 'nullable|string',
            'especialidade_id' => 'nullable|integer',
            'data_consulta' => 'required|date',
            'hora_consulta' => 'required',
            'motivo_consulta' => 'required|string',
            'observacoes' => 'nullable|string',
            'prioridade' => 'nullable|in:normal,urgente,emergencia',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Verifica se já existe consulta para este agendamento
            $consultaExistente = Consulta::where('agendamento_id', $request->agendamento_id)->first();
            
            if ($consultaExistente) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta já criada para este agendamento',
                    'consulta' => $consultaExistente
                ], 409);
            }

            // Usar paciente_id enviado pelo triage-service (não fazer lookup adicional)
            // Isso evita timeouts e dependências circulares

            // Criar consulta
            $consulta = Consulta::create([
                'agendamento_id' => $request->agendamento_id,
                'nid' => $request->nid,
                'paciente_id' => $request->paciente_id,
                'triagem_id' => $request->triagem_id,
                'medico' => $request->medico,
                'medico_id' => $request->medico_id,
                'tipo_consulta' => $request->tipo_consulta,
                'tipo_consulta_id' => $request->tipo_consulta_id,
                'especialidade' => $request->especialidade,
                'especialidade_id' => $request->especialidade_id,
                'data_consulta' => $request->data_consulta,
                'hora_consulta' => $request->hora_consulta,
                'motivo_consulta' => $request->motivo_consulta,
                'observacoes' => $request->observacoes,
                'prioridade' => $request->prioridade ?? 'normal',
                'status' => 'agendada',
                'sincronizado_triagem' => true,
                'data_sincronizacao_triagem' => now(),
            ]);

            // Registrar no histórico
            $consulta->registrarHistorico(
                'consulta_criada',
                null,
                'agendada',
                'Consulta criada a partir do agendamento da triagem'
            );

            // NÃO notificar triage-service - evitar loop circular
            // O triage-service receberá o consulta_id na resposta e atualizará localmente

            DB::commit();

            Log::info('Consulta criada com sucesso', [
                'consulta_id' => $consulta->id,
                'agendamento_id' => $request->agendamento_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Consulta criada com sucesso',
                'data' => $consulta->load(['historico', 'anexos'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao criar consulta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar consulta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar consultas agendadas
     * GET /api/consultas/agendadas
     */
    public function getAgendadas(Request $request)
    {
        try {
            $query = Consulta::agendadas();

            // Filtros
            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('data_consulta')) {
                $query->whereDate('data_consulta', $request->data_consulta);
            }

            if ($request->has('especialidade_id')) {
                $query->where('especialidade_id', $request->especialidade_id);
            }

            $consultas = $query->with(['historico', 'anexos'])
                ->orderBy('data_consulta')
                ->orderBy('hora_consulta')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Consultas agendadas',
                'data' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas agendadas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas agendadas'
            ], 500);
        }
    }

    /**
     * Buscar consultas de hoje
     * GET /api/consultas/hoje
     */
    public function getConsultasHoje(Request $request)
    {
        try {
            $query = Consulta::hoje();

            // Filtros
            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $consultas = $query->with(['historico', 'anexos'])
                ->orderBy('hora_consulta')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Consultas de hoje',
                'data' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas de hoje', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas de hoje'
            ], 500);
        }
    }

    /**
     * Buscar consulta por ID
     * GET /api/consultas/{id}
     */
    public function show($id)
    {
        try {
            $consulta = Consulta::with(['historico', 'anexos'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $consulta
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Consulta não encontrada'
            ], 404);
        }
    }

    /**
     * Listar médicos disponíveis para transferência (mesma especialidade da consulta)
     * GET /api/consultas/{id}/medicos-disponiveis
     */
    public function getMedicosDisponiveisParaTransferencia($id)
    {
        try {
            $consulta = Consulta::findOrFail($id);

            if (!$consulta->especialidade) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta não possui especialidade definida'
                ], 400);
            }

            // Buscar médicos da mesma especialidade, excluindo o médico atual
            $medicos = DB::connection('mysql')
                ->table('usuarios')
                ->select('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->join('consultas', 'consultas.medico_id', '=', 'usuarios.id')
                ->where('consultas.especialidade', '=', $consulta->especialidade)
                ->where('usuarios.ativo', true)
                ->where('usuarios.id', '!=', $consulta->medico_id) // Excluir médico atual
                ->groupBy('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->orderBy('usuarios.nome')
                ->get();

            // Se não encontrar médicos com histórico, buscar por cargo
            if ($medicos->isEmpty()) {
                $cargoPattern = $this->mapEspecialidadeToCargo($consulta->especialidade);
                
                $medicos = DB::connection('mysql')
                    ->table('usuarios')
                    ->select('id', 'nome', 'cargo', 'email')
                    ->where('ativo', true)
                    ->where('id', '!=', $consulta->medico_id)
                    ->where(function($query) use ($cargoPattern, $consulta) {
                        $query->where('cargo', 'like', "%{$cargoPattern}%")
                              ->orWhere('cargo', 'like', "%{$consulta->especialidade}%");
                    })
                    ->orderBy('nome')
                    ->get();

                Log::info('Buscando médicos por cargo da especialidade', [
                    'consulta_id' => $id,
                    'especialidade' => $consulta->especialidade,
                    'cargo_pattern' => $cargoPattern,
                    'total_medicos' => $medicos->count()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $medicos,
                'total' => $medicos->count(),
                'especialidade' => $consulta->especialidade,
                'medico_atual' => [
                    'id' => $consulta->medico_id,
                    'nome' => $consulta->medico
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar médicos disponíveis', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar médicos disponíveis'
            ], 500);
        }
    }

    /**
     * Buscar consultas por paciente
     * GET /api/consultas/paciente/{pacienteId}
     */
    public function getByPaciente($pacienteId)
    {
        try {
            $consultas = Consulta::where('paciente_id', $pacienteId)
                ->with(['historico', 'anexos'])
                ->orderBy('data_consulta', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas do paciente', [
                'paciente_id' => $pacienteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas do paciente'
            ], 500);
        }
    }

    /**
     * Buscar consultas por NID
     * GET /api/consultas/nid/{nid}
     */
    public function getByNid($nid)
    {
        try {
            $consultas = Consulta::where('nid', $nid)
                ->with(['historico', 'anexos'])
                ->orderBy('data_consulta', 'desc')
                ->get();

            // Buscar histórico adicional do patient-service
            $token = request()->bearerToken();
            $historicoPatient = $this->patientService->getHistoricoConsultas($nid, $token);

            return response()->json([
                'status' => 'success',
                'data' => $consultas,
                'historico_paciente' => $historicoPatient,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas por NID', [
                'nid' => $nid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas'
            ], 500);
        }
    }

    /**
     * Buscar consultas por médico
     * GET /api/consultas/medico/{medicoId}
     */
    public function getByMedico(Request $request, $medicoId)
    {
        try {
            $query = Consulta::where('medico_id', $medicoId);

            // Filtro por data
            if ($request->has('data_inicio') && $request->has('data_fim')) {
                $query->whereBetween('data_consulta', [$request->data_inicio, $request->data_fim]);
            }

            // Filtro por status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $consultas = $query->with(['historico', 'anexos'])
                ->orderBy('data_consulta', 'desc')
                ->orderBy('hora_consulta')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas do médico', [
                'medico_id' => $medicoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas do médico'
            ], 500);
        }
    }

    /**
     * Iniciar atendimento
     * POST /api/consultas/{id}/iniciar
     */
    public function iniciarAtendimento($id)
    {
        try {
            $consulta = Consulta::findOrFail($id);

            if ($consulta->status !== 'agendada') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta não está agendada'
                ], 400);
            }

            $consulta->iniciarAtendimento();

            // Notificar triage-service
            $this->triageService->atualizarStatusAgendamento(
                $consulta->agendamento_id,
                'em_atendimento',
                $consulta->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Atendimento iniciado',
                'data' => $consulta->fresh(['historico', 'anexos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao iniciar atendimento', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao iniciar atendimento'
            ], 500);
        }
    }

    /**
     * Atualizar dados da consulta (anamnese, exame físico, etc)
     * PUT /api/consultas/{id}/atualizar
     */
    public function atualizarConsulta(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'anamnese' => 'nullable|string',
            'exame_fisico' => 'nullable|string',
            'hipotese_diagnostica' => 'nullable|string',
            'prescricao' => 'nullable|string',
            'procedimentos' => 'nullable|string',
            'exames_solicitados' => 'nullable|string',
            'plano_tratamento' => 'nullable|string',
            'orientacoes' => 'nullable|string',
            'data_retorno' => 'nullable|date',
            'atestado_medico' => 'nullable|string',
            'dias_atestado' => 'nullable|integer',
            'encaminhamento' => 'nullable|string',
            // Campos de pagamento (para microserviços)
            'status_pagamento' => 'nullable|in:pendente,pago,cancelado',
            'forma_pagamento' => 'nullable|string',
            'data_pagamento' => 'nullable|date',
            'valor_consulta' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consulta = Consulta::findOrFail($id);

            if ($consulta->status === 'finalizada') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta já finalizada'
                ], 400);
            }

            $consulta->update($request->only([
                'anamnese',
                'exame_fisico',
                'hipotese_diagnostica',
                'prescricao',
                'procedimentos',
                'exames_solicitados',
                'plano_tratamento',
                'orientacoes',
                'data_retorno',
                'atestado_medico',
                'dias_atestado',
                'encaminhamento',
                'status_pagamento',
                'forma_pagamento',
                'data_pagamento',
                'valor_consulta',
            ]));

            $consulta->registrarHistorico('consulta_atualizada', null, null, 'Dados da consulta atualizados');

            // Se houver exames solicitados, notificar laboratory-service
            if ($request->has('exames_solicitados') && !empty($request->exames_solicitados)) {
                $this->laboratoryService->solicitarExames(
                    $consulta->id,
                    $consulta->paciente_id,
                    $request->exames_solicitados,
                    $request->bearerToken()
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Consulta atualizada com sucesso',
                'data' => $consulta->fresh(['historico', 'anexos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar consulta', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar consulta'
            ], 500);
        }
    }

    /**
     * Finalizar consulta
     * POST /api/consultas/{id}/finalizar
     */
    public function finalizarConsulta($id)
    {
        try {
            $consulta = Consulta::findOrFail($id);

            if ($consulta->status !== 'em_atendimento') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta não está em atendimento'
                ], 400);
            }

            $consulta->finalizarAtendimento();

            // Notificar triage-service
            $this->triageService->atualizarStatusAgendamento(
                $consulta->agendamento_id,
                'finalizada',
                $consulta->id
            );

            // Atualizar histórico médico do paciente
            $this->patientService->atualizarHistoricoMedico(
                $consulta->paciente_id,
                [
                    'consulta_id' => $consulta->id,
                    'data_consulta' => $consulta->data_consulta,
                    'diagnostico' => $consulta->hipotese_diagnostica,
                    'tratamento' => $consulta->plano_tratamento,
                ],
                request()->bearerToken()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Consulta finalizada com sucesso',
                'data' => $consulta->fresh(['historico', 'anexos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao finalizar consulta', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao finalizar consulta'
            ], 500);
        }
    }

    /**
     * Cancelar consulta
     * POST /api/consultas/{id}/cancelar
     */
    public function cancelarConsulta(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consulta = Consulta::findOrFail($id);

            if ($consulta->status === 'finalizada') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta já finalizada não pode ser cancelada'
                ], 400);
            }

            $consulta->cancelar($request->motivo);

            // Notificar triage-service
            $this->triageService->atualizarStatusAgendamento(
                $consulta->agendamento_id,
                'cancelada',
                $consulta->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Consulta cancelada com sucesso',
                'data' => $consulta->fresh(['historico', 'anexos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar consulta', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao cancelar consulta'
            ], 500);
        }
    }

    /**
     * Transferir médico
     * POST /api/consultas/{id}/transferir-medico
     */
    public function transferirMedico(Request $request, $id)
    {
        // Aceitar tanto o formato antigo quanto o novo do frontend
        $validator = Validator::make($request->all(), [
            'novo_medico' => 'required_without:medico_destino_id|string',
            'novo_medico_id' => 'required_without:medico_destino_id|integer',
            'medico_destino_id' => 'required_without:novo_medico_id|integer',
            'motivo' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consulta = Consulta::findOrFail($id);

            if ($consulta->status === 'finalizada') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consulta finalizada não pode ter médico transferido'
                ], 400);
            }

            // Suportar ambos os formatos: novo_medico_id ou medico_destino_id
            $medicoDestinoId = $request->novo_medico_id ?? $request->medico_destino_id;
            $medicoDestinoNome = $request->novo_medico;

            // Se não tiver o nome do médico, buscar do banco
            if (!$medicoDestinoNome) {
                try {
                    $authDb = DB::connection('mysql')->getPdo();
                    $stmt = $authDb->prepare("SELECT nome FROM usuarios WHERE id = ? LIMIT 1");
                    $stmt->execute([$medicoDestinoId]);
                    $medico = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $medicoDestinoNome = $medico ? $medico['nome'] : 'Médico ID: ' . $medicoDestinoId;
                } catch (\Exception $e) {
                    $medicoDestinoNome = 'Médico ID: ' . $medicoDestinoId;
                }
            }

            $consulta->transferirMedico(
                $medicoDestinoNome,
                $medicoDestinoId,
                $request->motivo
            );

            // Sincronizar com triage-service se houver agendamento_id
            if ($consulta->agendamento_id) {
                try {
                    $this->triageService->atualizarMedicoAgendamento(
                        $consulta->agendamento_id,
                        $medicoDestinoNome,
                        $medicoDestinoId,
                        null, // especialidade permanece a mesma
                        null  // especialidade_id permanece o mesmo
                    );
                    
                    Log::info('Agendamento atualizado no triage-service', [
                        'agendamento_id' => $consulta->agendamento_id,
                        'novo_medico' => $medicoDestinoNome,
                        'novo_medico_id' => $medicoDestinoId
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Erro ao atualizar agendamento no triage-service', [
                        'agendamento_id' => $consulta->agendamento_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Médico transferido com sucesso',
                'data' => $consulta->fresh(['historico', 'anexos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao transferir médico', [
                'consulta_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao transferir médico'
            ], 500);
        }
    }

    /**
     * Transferir para outra especialidade
     * POST /api/consultas/{id}/transferir-especialidade
     */
    public function transferirEspecialidade(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'especialidade_destino' => 'required|string',
            'especialidade_destino_id' => 'nullable|integer',
            'medico_destino_id' => 'required|integer',
            'medico_destino' => 'nullable|string',
            'motivo' => 'required|string|min:10',
            'observacoes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::warning('Validação falhou na transferência de especialidade', [
                'consulta_id' => $id,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consulta = Consulta::findOrFail($id);

            // Se a consulta estiver finalizada ou cancelada, criar uma nova consulta para transferência
            if (in_array($consulta->status, ['finalizada', 'cancelada'])) {
                Log::info('Consulta finalizada/cancelada - criando nova consulta para transferência', [
                    'consulta_antiga_id' => $consulta->id,
                    'status_antigo' => $consulta->status
                ]);

                // Criar nova consulta baseada na anterior
                $novaConsulta = new Consulta([
                    'agendamento_id' => $consulta->agendamento_id,
                    'nid' => $consulta->nid,
                    'paciente_id' => $consulta->paciente_id,
                    'triagem_id' => $consulta->triagem_id,
                    'medico' => $consulta->medico,
                    'medico_id' => $consulta->medico_id,
                    'tipo_consulta' => $consulta->tipo_consulta,
                    'tipo_consulta_id' => $consulta->tipo_consulta_id,
                    'especialidade' => $consulta->especialidade,
                    'especialidade_id' => $consulta->especialidade_id,
                    'data_consulta' => now()->toDateString(),
                    'hora_consulta' => now()->format('H:i'),
                    'motivo_consulta' => 'Transferência de especialidade - Consulta anterior finalizada',
                    'status' => 'agendada',
                    'prioridade' => $consulta->prioridade ?? 'normal',
                ]);
                $novaConsulta->save();

                // Usar a nova consulta para transferência
                $consulta = $novaConsulta;

                Log::info('Nova consulta criada para transferência', [
                    'nova_consulta_id' => $consulta->id
                ]);
            }

            // Validar se a especialidade de destino é diferente da origem
            if ($consulta->especialidade === $request->especialidade_destino) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A especialidade de destino deve ser diferente da atual'
                ], 400);
            }

            // Buscar dados do médico de destino do auth-service
            $medicoDestino = null;
            $medicoDestinoData = null;
            
            try {
                $authDb = DB::connection('mysql')->getPdo();
                $authDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                
                $stmt = $authDb->prepare("SELECT id, nome, cargo FROM usuarios WHERE id = ? LIMIT 1");
                $stmt->execute([$request->medico_destino_id]);
                $medicoDestinoData = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$medicoDestinoData) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Médico de destino não encontrado'
                    ], 404);
                }
                
                $medicoDestino = $medicoDestinoData['nome'];
                
                Log::info('Médico de destino encontrado', [
                    'medico_id' => $request->medico_destino_id,
                    'nome' => $medicoDestino,
                    'cargo' => $medicoDestinoData['cargo']
                ]);
                
            } catch (\Exception $e) {
                Log::error('Erro ao buscar médico de destino', [
                    'medico_id' => $request->medico_destino_id,
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao buscar dados do médico de destino'
                ], 500);
            }

            // Buscar ID da especialidade se não foi fornecido
            $especialidadeDestinoId = $request->especialidade_destino_id;
            if (!$especialidadeDestinoId) {
                try {
                    $especialidade = DB::table('especialidades')
                        ->where('nome', $request->especialidade_destino)
                        ->first();
                    $especialidadeDestinoId = $especialidade ? $especialidade->id : null;
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar especialidade', ['especialidade' => $request->especialidade_destino]);
                }
            }

            $consulta->transferirEspecialidade(
                $request->especialidade_destino,
                $especialidadeDestinoId,
                $medicoDestino,
                $request->medico_destino_id,
                $request->motivo,
                $request->observacoes
            );

            // Atualizar agendamento no triage-service se houver agendamento_id
            if ($consulta->agendamento_id) {
                try {
                    $this->triageService->atualizarMedicoAgendamento(
                        $consulta->agendamento_id,
                        $medicoDestino,
                        $request->medico_destino_id,
                        $request->especialidade_destino,
                        $especialidadeDestinoId
                    );
                } catch (\Exception $e) {
                    Log::warning('Erro ao atualizar agendamento no triage-service', [
                        'agendamento_id' => $consulta->agendamento_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Paciente transferido para ' . $request->especialidade_destino . ' com sucesso',
                'data' => $consulta->fresh(['historico', 'anexos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao transferir para especialidade', [
                'consulta_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao transferir paciente para especialidade'
            ], 500);
        }
    }

    /**
     * Listar consultas transferidas para especialidade
     * GET /api/consultas/transferidos-especialidade
     */
    public function listarTransferidosEspecialidade(Request $request)
    {
        try {
            $query = Consulta::where('status', 'transferido_especialidade')
                ->where('transferido', true)
                ->orderBy('updated_at', 'desc');

            // Filtros opcionais
            if ($request->has('especialidade')) {
                $query->where('especialidade', 'like', '%' . $request->especialidade . '%');
            }

            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('data_inicio')) {
                $query->whereDate('updated_at', '>=', $request->data_inicio);
            }

            if ($request->has('data_fim')) {
                $query->whereDate('updated_at', '<=', $request->data_fim);
            }

            // Incluir informações do paciente se necessário
            $consultas = $query->with(['transferenciaHistorico' => function($q) {
                $q->where('tipo', 'especialidade')->orderBy('created_at', 'desc');
            }])->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $consultas
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar consultas transferidas para especialidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas transferidas'
            ], 500);
        }
    }

    /**
     * Estatísticas de consultas
     * GET /api/consultas/estatisticas
     */
    public function estatisticas(Request $request)
    {
        try {
            $dataInicio = $request->get('data_inicio', now()->startOfMonth());
            $dataFim = $request->get('data_fim', now()->endOfMonth());

            $query = Consulta::whereBetween('data_consulta', [$dataInicio, $dataFim]);

            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            $stats = [
                'total_consultas' => (clone $query)->count(),
                'agendadas' => (clone $query)->where('status', 'agendada')->count(),
                'em_atendimento' => (clone $query)->where('status', 'em_atendimento')->count(),
                'finalizadas' => (clone $query)->where('status', 'finalizada')->count(),
                'canceladas' => (clone $query)->where('status', 'cancelada')->count(),
                'nao_compareceu' => (clone $query)->where('status', 'nao_compareceu')->count(),
                'por_especialidade' => (clone $query)
                    ->select('especialidade', DB::raw('count(*) as total'))
                    ->groupBy('especialidade')
                    ->get(),
                'por_tipo_consulta' => (clone $query)
                    ->select('tipo_consulta', DB::raw('count(*) as total'))
                    ->groupBy('tipo_consulta')
                    ->get(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar estatísticas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar estatísticas'
            ], 500);
        }
    }

    /**
     * Buscar consultas pendentes (em espera/agendadas)
     * GET /api/consultas/pendentes
     */
    public function getPendentes(Request $request)
    {
        try {
            $query = Consulta::whereIn('status', ['agendada', 'em_atendimento']);

            // Filtros
            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('data_consulta')) {
                $query->whereDate('data_consulta', $request->data_consulta);
            } else {
                // Por padrão, apenas consultas de hoje ou futuras
                $query->whereDate('data_consulta', '>=', today());
            }

            $consultas = $query->with(['historico', 'anexos'])
                ->orderBy('data_consulta')
                ->orderBy('hora_consulta')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Consultas pendentes',
                'data' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas pendentes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas pendentes'
            ], 500);
        }
    }

    /**
     * Buscar consultas com retorno para entrega de exames
     * GET /api/consultas/retorno-exames
     */
    public function getRetornoExames(Request $request)
    {
        try {
            // Consultas finalizadas que solicitaram exames
            $query = Consulta::where('status', 'finalizada')
                ->whereNotNull('exames_solicitados')
                ->where('exames_solicitados', '!=', '');

            // Filtros
            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('paciente_id')) {
                $query->where('paciente_id', $request->paciente_id);
            }

            if ($request->has('nid')) {
                $query->where('nid', $request->nid);
            }

            // Ordenar por data de retorno ou data da consulta
            $consultas = $query->with(['historico', 'anexos'])
                ->orderByRaw('COALESCE(data_retorno, data_consulta) DESC')
                ->limit(50)
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Consultas com retorno para exames',
                'data' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar consultas com retorno de exames', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar consultas com retorno de exames'
            ], 500);
        }
    }

    /**
     * Listar médicos por especialidade
     * GET /api/consultas/medicos-por-especialidade?especialidade={nome}
     */
    public function getMedicosPorEspecialidade(Request $request)
    {
        try {
            $especialidade = $request->query('especialidade');

            if (!$especialidade) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parâmetro especialidade é obrigatório'
                ], 400);
            }

            // Buscar médicos que já atenderam nesta especialidade
            // Isso garante que são médicos que trabalham nessa área
            $medicos = DB::connection('mysql')
                ->table('usuarios')
                ->select('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->join('consultas', 'consultas.medico_id', '=', 'usuarios.id')
                ->where('consultas.especialidade', '=', $especialidade)
                ->where('usuarios.ativo', true)
                ->groupBy('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->orderBy('usuarios.nome')
                ->get();

            // Se não encontrar médicos com histórico, buscar todos os médicos ativos
            if ($medicos->isEmpty()) {
                $medicos = DB::connection('mysql')
                    ->table('usuarios')
                    ->select('id', 'nome', 'cargo', 'email')
                    ->where('ativo', true)
                    ->where('cargo', 'like', '%médico%')
                    ->orWhere('cargo', 'like', '%Médico%')
                    ->orderBy('nome')
                    ->get();

                Log::info('Nenhum médico encontrado com histórico na especialidade, retornando todos os médicos ativos', [
                    'especialidade' => $especialidade,
                    'total_medicos' => $medicos->count()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $medicos,
                'total' => $medicos->count(),
                'especialidade' => $especialidade
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar médicos por especialidade', [
                'error' => $e->getMessage(),
                'especialidade' => $request->query('especialidade')
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar médicos'
            ], 500);
        }
    }

    /**
     * Buscar médicos por especialidade (via POST com payload)
     * POST /api/consultas/buscar-medicos-especialidade
     */
    public function buscarMedicosPorEspecialidade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'especialidade' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Campo especialidade é obrigatório',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $especialidade = $request->especialidade;

            // Buscar médicos que já atenderam nesta especialidade
            $medicos = DB::connection('mysql')
                ->table('usuarios')
                ->select('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->join('consultas', 'consultas.medico_id', '=', 'usuarios.id')
                ->where('consultas.especialidade', '=', $especialidade)
                ->where('usuarios.ativo', true)
                ->groupBy('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->orderBy('usuarios.nome')
                ->get();

            // Se não encontrar médicos com histórico, buscar médicos ativos pelo cargo
            if ($medicos->isEmpty()) {
                // Mapear especialidade para padrão de cargo
                $cargoPattern = $this->mapEspecialidadeToCargo($especialidade);
                
                $medicos = DB::connection('mysql')
                    ->table('usuarios')
                    ->select('id', 'nome', 'cargo', 'email')
                    ->where('ativo', true)
                    ->where(function($query) use ($cargoPattern, $especialidade) {
                        $query->where('cargo', 'like', "%{$cargoPattern}%")
                              ->orWhere('cargo', 'like', "%{$especialidade}%");
                    })
                    ->orderBy('nome')
                    ->get();

                Log::info('Buscando médicos por cargo da especialidade', [
                    'especialidade' => $especialidade,
                    'cargo_pattern' => $cargoPattern,
                    'total_medicos' => $medicos->count()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $medicos,
                'total' => $medicos->count(),
                'especialidade' => $especialidade
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar médicos por especialidade', [
                'error' => $e->getMessage(),
                'especialidade' => $request->especialidade
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar médicos'
            ], 500);
        }
    }

    /**
     * Mapeia especialidade para padrão de cargo
     */
    private function mapEspecialidadeToCargo(string $especialidade): string
    {
        $mapeamento = [
            'Cardiologia' => 'Cardiologista',
            'Ortopedia' => 'Ortopedista',
            'Pediatria' => 'Pediatra',
            'Ginecologia' => 'Ginecologista',
            'Dermatologia' => 'Dermatologista',
            'Oftalmologia' => 'Oftalmologista',
            'Neurologia' => 'Neurologista',
            'Psiquiatria' => 'Psiquiatra',
            'Otorrinolaringologia' => 'Otorrinolaringologista',
            'Clínica Geral' => 'Clínico Geral',
        ];

        return $mapeamento[$especialidade] ?? $especialidade;
    }

    /**
     * Listar médicos (compatibilidade com frontend)
     * GET /api/medicos?especialidade={nome}
     */
    public function listarMedicos(Request $request)
    {
        try {
            $especialidade = $request->query('especialidade');

            if (!$especialidade) {
                // Se não tiver especialidade, retorna todos os médicos ativos
                $medicos = DB::connection('mysql')
                    ->table('usuarios')
                    ->select('id', 'nome', 'cargo', 'email')
                    ->where('ativo', true)
                    ->where(function($query) {
                        $query->where('cargo', 'like', '%médico%')
                              ->orWhere('cargo', 'like', '%Médico%');
                    })
                    ->orderBy('nome')
                    ->get();

                return response()->json([
                    'status' => 'success',
                    'data' => $medicos,
                    'total' => $medicos->count()
                ]);
            }

            // Buscar médicos que já atenderam nesta especialidade
            $medicos = DB::connection('mysql')
                ->table('usuarios')
                ->select('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->join('consultas', 'consultas.medico_id', '=', 'usuarios.id')
                ->where('consultas.especialidade', '=', $especialidade)
                ->where('usuarios.ativo', true)
                ->where(function($query) {
                    $query->where('usuarios.cargo', 'like', '%médico%')
                          ->orWhere('usuarios.cargo', 'like', '%Médico%');
                })
                ->groupBy('usuarios.id', 'usuarios.nome', 'usuarios.cargo', 'usuarios.email')
                ->orderBy('usuarios.nome')
                ->get();

            // Se não encontrar médicos com histórico, buscar por cargo
            if ($medicos->isEmpty()) {
                $cargoPattern = $this->mapEspecialidadeToCargo($especialidade);
                
                $medicos = DB::connection('mysql')
                    ->table('usuarios')
                    ->select('id', 'nome', 'cargo', 'email')
                    ->where('ativo', true)
                    ->where(function($query) use ($cargoPattern, $especialidade) {
                        $query->where('cargo', 'like', "%{$cargoPattern}%")
                              ->orWhere('cargo', 'like', "%{$especialidade}%");
                    })
                    ->where(function($query) {
                        $query->where('cargo', 'like', '%médico%')
                              ->orWhere('cargo', 'like', '%Médico%');
                    })
                    ->orderBy('nome')
                    ->get();

                Log::info('Buscando médicos por cargo da especialidade', [
                    'especialidade' => $especialidade,
                    'cargo_pattern' => $cargoPattern,
                    'total_medicos' => $medicos->count()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $medicos,
                'total' => $medicos->count(),
                'especialidade' => $especialidade
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar médicos', [
                'error' => $e->getMessage(),
                'especialidade' => $request->query('especialidade')
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao listar médicos'
            ], 500);
        }
    }

    /**
     * Listar especialidades (compatibilidade com frontend)
     * GET /api/especialidades
     */
    public function listarEspecialidades(Request $request)
    {
        try {
            // Verificar quais colunas existem na tabela
            $columns = DB::getSchemaBuilder()->getColumnListing('especialidades');
            
            $select = ['id', 'nome'];
            foreach (['codigo', 'descricao', 'cor', 'icone', 'requer_encaminhamento'] as $col) {
                if (in_array($col, $columns)) {
                    $select[] = $col;
                }
            }

            $query = DB::table('especialidades')
                ->select($select);

            // Filtrar por ativo apenas se a coluna existir
            if (in_array('ativo', $columns)) {
                $query->where('ativo', true);
            }

            // Filtro por nome se fornecido
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('nome', 'like', "%{$search}%");
            }

            $especialidades = $query->orderBy('nome')->get();

            return response()->json([
                'status' => 'success',
                'data' => $especialidades,
                'total' => $especialidades->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar especialidades', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao listar especialidades'
            ], 500);
        }
    }

    /**
     * Finalizar consulta com prescrições e exames
     * POST /api/consultas/finalizar
     */
    public function finalizar(FinalizarConsultaRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Valores default para campos opcionais
            $status = $request->status ?? 'finalizada';
            $diagnostico = $request->diagnostico ?? 'Consulta realizada';
            $temPrescricao = $request->temPrescricao ?? false;
            $temExames = $request->temExames ?? false;
            $aguardandoExames = $request->aguardandoExames ?? false;
            $deveFinalizarConsulta = $request->deveFinalizarConsulta ?? true;
            $deveTerminarCiclo = $request->deveTerminarCiclo ?? true;
            $tipoFinalizacao = $request->tipoFinalizacao ?? 'basica';
            
            // 1. Buscar consulta existente pelo agendamento_id
            $consulta = Consulta::where('agendamento_id', $request->agendamentoId)->first();
            
            if (!$consulta) {
                Log::warning('Consulta não encontrada para agendamento', [
                    'agendamento_id' => $request->agendamentoId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Consulta não encontrada para este agendamento'
                ], 404);
            }
            
            // 1.1. Verificar se a consulta já foi finalizada
            if (in_array($consulta->status, ['finalizada', 'concluida', 'finalizada_com_exames'])) {
                Log::info('Tentativa de finalizar consulta já finalizada', [
                    'consulta_id' => $consulta->id,
                    'agendamento_id' => $request->agendamentoId,
                    'status_atual' => $consulta->status
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Esta consulta já foi finalizada anteriormente',
                    'data' => [
                        'consulta_id' => $consulta->id,
                        'status_atual' => $consulta->status,
                        'data_finalizacao' => $consulta->data_hora_fim
                    ]
                ], 409); // 409 Conflict
            }
            
            // 2. Atualizar dados da consulta
            $consulta->update([
                'motivo_consulta' => $request->queixaPrincipal ?? $request->sintomas ?? 'Consulta realizada',
                'anamnese' => $request->historiaDoenca ?? $request->historico,
                'exame_fisico' => $request->exameClinico,
                'hipotese_diagnostica' => $diagnostico,
                'plano_tratamento' => $request->conduta ?? $request->recomendacoes,
                'observacoes' => $request->observacoes ?? $request->recomendacoes,
                'status' => $status,
                'data_hora_fim' => $request->dataAlta ?? now(),
            ]);
            
            // 3. Salvar prescrições (se houver)
            if (!empty($request->prescricoes)) {
                foreach ($request->prescricoes as $prescricaoData) {
                    Prescricao::create([
                        'consulta_id' => $consulta->id,
                        'nid' => $consulta->nid,
                        'paciente_id' => $consulta->paciente_id,
                        'medico_id' => $consulta->medico_id,
                        'medico_nome' => $consulta->medico,
                        'medicamento' => $prescricaoData['medicamento'],
                        'dosagem' => $prescricaoData['dosagem'],
                        'forma_farmaceutica' => $prescricaoData['unidade'] ?? null,
                        'via_administracao' => $prescricaoData['viaAdministracao'],
                        'frequencia' => $prescricaoData['doseDiaria'] . 'x ao dia',
                        'quantidade_por_dose' => $prescricaoData['quantidade'],
                        'unidade_medida' => $prescricaoData['unidade'],
                        'horarios_list' => json_encode($prescricaoData['horarios']),
                        'duracao_dias' => $prescricaoData['numeroDias'],
                        'data_inicio' => now(),
                        'data_fim' => now()->addDays($prescricaoData['numeroDias']),
                        'observacoes' => $prescricaoData['comentario'] ?? null,
                        'orientacoes_uso' => $prescricaoData['comentario'] ?? null,
                        'quantidade_total' => $prescricaoData['quantidade'] * $prescricaoData['doseDiaria'] * $prescricaoData['numeroDias'],
                        'status' => 'prescrita',
                    ]);
                }
                
                Log::info('Prescrições salvas', [
                    'consulta_id' => $consulta->id,
                    'quantidade' => count($request->prescricoes)
                ]);
            }
            
            // 4. Salvar exames solicitados (se houver)
            $examesCriados = [];
            if (!empty($request->exames)) {
                foreach ($request->exames as $exameData) {
                    // Mapear prioridade/estado para urgencia (ENUM: normal, urgente, emergencia)
                    $prioridade = strtolower($exameData['prioridade'] ?? $exameData['estado'] ?? 'normal');
                    $urgenciaValida = in_array($prioridade, ['normal', 'urgente', 'emergencia'])
                        ? $prioridade
                        : 'normal';

                    $exame = Exame::create([
                        'consulta_id' => $consulta->id,
                        'nid' => $consulta->nid,
                        'paciente_id' => $consulta->paciente_id,
                        'medico_solicitante_id' => $consulta->medico_id,
                        'medico_solicitante_nome' => $consulta->medico,
                        'nome_exame' => $exameData['nome'],
                        'tipo_exame' => $exameData['examesSolicitados'] ?? $exameData['nome'],
                        'descricao' => $exameData['examesSolicitados'] ?? $exameData['nome'],
                        'indicacao_clinica' => $request->diagnostico,
                        'observacoes_solicitacao' => $exameData['observacoes'] ?? null,
                        'data_solicitacao' => $exameData['dataSolicitacao'] ?? now(),
                        'status' => 'solicitado',  // novo exame sempre começa como solicitado
                        'urgencia' => $urgenciaValida,
                    ]);
                    
                    $examesCriados[] = [
                        'id' => $exame->id,
                        'nome' => $exame->nome_exame,
                        'tipo' => $exame->tipo_exame,
                        'observacoes' => $exame->observacoes_solicitacao,
                        'data_solicitacao' => $exame->data_solicitacao,
                    ];
                }
                
                Log::info('Exames salvos', [
                    'consulta_id' => $consulta->id,
                    'quantidade' => count($request->exames)
                ]);
                
                // 5. Enviar exames para patient-service
                if (!empty($examesCriados)) {
                    $this->patientService->enviarExamesSolicitados(
                        $consulta->nid,
                        $examesCriados,
                        [
                            'consulta_id' => $consulta->id,
                            'medico_id' => $consulta->medico_id,
                            'medico' => $consulta->medico,
                        ],
                        request()->bearerToken()
                    );
                }
            }
            
            // 6. Atualizar status do agendamento no triage-service
            if ($deveTerminarCiclo) {
                $statusAgendamento = 'finalizado';
            } else if ($aguardandoExames) {
                $statusAgendamento = 'aguardando_exames';
            } else {
                $statusAgendamento = 'em_atendimento';
            }
            
            $this->triageService->atualizarStatusAgendamento(
                $consulta->agendamento_id,
                $statusAgendamento,
                $consulta->id
            );
            
            Log::info('Status do agendamento atualizado', [
                'agendamento_id' => $consulta->agendamento_id,
                'status' => $statusAgendamento
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Consulta finalizada com sucesso',
                'data' => [
                    'consulta_id' => $consulta->id,
                    'agendamento_id' => $consulta->agendamento_id,
                    'prescricoes_count' => count($request->prescricoes ?? []),
                    'exames_count' => count($request->exames ?? []),
                    'deve_terminar_ciclo' => $request->deveTerminarCiclo,
                    'status' => $consulta->status,
                    'tipo_finalizacao' => $request->tipoFinalizacao,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao finalizar consulta', [
                'agendamento_id' => $request->agendamentoId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao finalizar consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Solicitar exames para uma consulta
     * POST /api/consultas/{id}/exames
     */
    public function solicitarExames(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'exames'                    => 'required|array|min:1',
            'exames.*.tipo_exame'       => 'required|string|max:255',
            'exames.*.prioridade'       => 'nullable|in:normal,urgente,critica',
            'exames.*.status'           => 'nullable|string',
            'queixa_principal'          => 'nullable|string|max:1000',
            'historico'                 => 'nullable|string|max:2000',
            'exame_fisico'              => 'nullable|string|max:1000',
            'hipotese_diagnostica'      => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inv�lidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consulta = Consulta::findOrFail($id);
            $consulta->update([
                'queixa_principal' => $request->queixa_principal ?? $consulta->queixa_principal,
                'historico' => $request->historico ?? $consulta->historico,
                'exame_fisico' => $request->exame_fisico ?? $consulta->exame_fisico,
                'hipotese_diagnostica' => $request->hipotese_diagnostica ?? $consulta->hipotese_diagnostica,
                'status' => 'aguardando_exames',
            ]);

            $patientServiceUrl = env('PATIENT_SERVICE_URL', 'http://127.0.0.1:8002');
            $serviceToken = env('SERVICE_TOKEN', 'shared-secret-token');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Service-Token' => $serviceToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post("{$patientServiceUrl}/api/solicitacoes-exames", [
                'consulta_id' => $consulta->id,
                'paciente_id' => $consulta->paciente_id,
                'nid' => $consulta->nid,
                'medico_solicitante' => $consulta->medico,
                'medico_solicitante_id' => $consulta->medico_id,
                'exames_solicitados' => $request->exames,
                'queixa_principal' => $request->queixa_principal,
                'status' => 'pending',
            ]);

            if ($consulta->agendamento_id) {
                $this->triageService->atualizarStatusAgendamento($consulta->agendamento_id, 'aguardando_exames', $consulta->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Exames solicitados com sucesso.',
                'data' => ['consulta_id' => $consulta->id, 'exames_solicitados' => count($request->exames)]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao solicitar exames', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Listar consultas realizadas (finalizadas)
     * GET /api/consultas/realizadas
     */
    public function realizadas(Request $request)
    {
        $query = Consulta::whereIn('status', ['finalizada', 'finalizada_com_exames'])
            ->with(['prescricoes', 'exames']);

        // Filtro por médico (para painel do médico)
        if ($request->has('medico_id')) {
            $query->where('medico_id', $request->medico_id);
        }

        // Filtro por paciente
        if ($request->has('nid')) {
            $query->where('nid', $request->nid);
        }

        // Filtro por data
        if ($request->has('data_inicio')) {
            $query->whereDate('data_consulta', '>=', $request->data_inicio);
        }

        if ($request->has('data_fim')) {
            $query->whereDate('data_consulta', '<=', $request->data_fim);
        }

        $consultas = $query->orderBy('data_consulta', 'desc')
            ->orderBy('hora_consulta', 'desc')
            ->paginate(15);

        return response()->json($consultas);
    }
}
