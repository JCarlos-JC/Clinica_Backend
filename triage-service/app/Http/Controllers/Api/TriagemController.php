<?php
// filepath: services/triage-service/app/Http/Controllers/Api/TriagemController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Triagem;
use App\Models\SinaisVitais;
use App\Services\PatientServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class TriagemController extends Controller
{
    protected $patientService;
    
    public function __construct(PatientServiceClient $patientService)
    {
        $this->patientService = $patientService;
    }
    
    /**
     * List all triages with filters
     * GET /api/triagens
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        // Try remote first: fetch pending solicitacoes from patient-service
        try {
            $remote = $this->patientService->getPendentes();
        } catch (\Exception $e) {
            Log::warning('Error fetching remote pendentes in index: ' . $e->getMessage());
            $remote = null;
        }

        if (is_array($remote) && count($remote) > 0) {
            $page = LengthAwarePaginator::resolveCurrentPage();
            $items = array_slice($remote, ($page - 1) * $perPage, $perPage);
            
            // ✅ RETORNAR DIRETAMENTE O ARRAY, não um objeto paginator
            // O frontend espera receber um array de solicitações
            return response()->json([
                'status' => 'success',
                'data' => $items,
                'meta' => [
                    'total' => count($remote),
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil(count($remote) / $perPage)
                ]
            ]);
        }

        // Fallback to local triagens (guarded in try/catch in case DB schema incomplete)
        try {
            $query = Triagem::with('sinaisVitais')->triagemInicial();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by urgency
            if ($request->has('estado_urgencia')) {
                $query->where('estado_urgencia', $request->estado_urgencia);
            }

            // Search by NID, nome, apelido
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Filter by date range
            if ($request->has('data_inicio')) {
                $query->whereDate('created_at', '>=', $request->data_inicio);
            }

            if ($request->has('data_fim')) {
                $query->whereDate('created_at', '<=', $request->data_fim);
            }

            $triagens = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $triagens
            ]);
        } catch (\Exception $e) {
            Log::error('Error querying local triagens in index: ' . $e->getMessage());

            // As last resort, return empty paginator shape
            $paginator = new LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => $request->url(),
                'query' => $request->query()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $paginator
            ]);
        }
    }
    
    /**
     * Get pending triages (aguardando triagem)
     * Matches: pacientesTriagemPendente from TriagemPaciente.jsx
     * GET /api/triagens/pendentes
     */
    public function pendentes(Request $request)
    {
        $query = Triagem::aguardandoTriagem();
        
        // Search filter (matches searchPendentes)
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $triagens = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $triagens,
            'total' => $triagens->count()
        ]);
    }
    
    /**
     * Get completed triages (triagem concluída)
     * Matches: triagensConcluidasLista from TriagemPaciente.jsx
     * GET /api/triagens/concluidas
     */
    public function concluidas(Request $request)
    {
        $query = Triagem::with('sinaisVitais')
                        ->triagemConcluida();
        
        // Search filter (matches searchConcluidas)
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $triagens = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $triagens,
            'total' => $triagens->count()
        ]);
    }
    
    /**
     * Create triage from patient-service request
     * POST /api/services/triagens (service-to-service)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|integer',
            'triagem_id' => 'required|integer',
            'nid' => 'nullable|string',
            'nome' => 'nullable|string',
            'apelido' => 'nullable|string',
            'genero' => 'nullable|string',
            'data_nascimento' => 'nullable|date',
            // allow missing estado_urgencia from frontend and default below
            'estado_urgencia' => 'nullable|in:emergencia,urgente,normal',
            'tipo_utente' => 'nullable|string',
            'observacoes' => 'nullable|string',
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
            
            // Create triage record
            $triagem = Triagem::create([
                'codigo_triagem' => Triagem::gerarCodigoTriagem(),
                'paciente_id' => $request->paciente_id,
                'triagem_id' => $request->triagem_id,
                'nid' => $request->nid,
                'nome' => $request->nome,
                'apelido' => $request->apelido,
                'genero' => $request->genero,
                'data_nascimento' => $request->data_nascimento,
                'estado_urgencia' => $request->estado_urgencia ?? 'normal',
                'tipo_utente' => $request->tipo_utente,
                'tipo_triagem' => 'inicial',
                'status' => 'aguardando_triagem',
                'data_cadastro' => now(),
                'observacoes' => $request->observacoes,
            ]);
            
            DB::commit();
            
            Log::info('Triagem criada com sucesso', [
                'triagem_id' => $triagem->id,
                'paciente_id' => $request->paciente_id
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Triagem criada com sucesso',
                'data' => $triagem
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao criar triagem: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar triagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Perform triage (collect vital signs)
     * Matches: handleFinishTriagem from TriagemPaciente.jsx
     * POST /api/triagens/{id}/realizar
     */
    public function realizarTriagem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pressao_arterial' => 'required|string|regex:/^\d{3}\/\d{2}$/',
            'peso' => 'required|numeric|min:0',
            'altura' => 'required|numeric|min:0',
            'frequencia_cardiaca' => 'required|integer|min:0',
            'temperatura' => 'required|numeric|min:30|max:45',
            'oximetria' => 'nullable|integer|min:0|max:100',
            'glicemia_capilar' => 'nullable|integer|min:0',
            'enfermeiro_id' => 'nullable|integer',
            'enfermeiro_nome' => 'nullable|string',
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
            
            $triagem = Triagem::findOrFail($id);
            
            if ($triagem->status !== 'aguardando_triagem' && $triagem->status !== 'em_triagem') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta triagem não pode ser realizada no momento'
                ], 400);
            }
            
            // Update triage status
            $triagem->update([
                'status' => 'em_triagem',
                'data_hora_inicio' => $triagem->data_hora_inicio ?? now(),
                'enfermeiro_id' => $request->enfermeiro_id,
                'enfermeiro_nome' => $request->enfermeiro_nome,
            ]);
            
            // Create or update vital signs
            $sinaisVitais = SinaisVitais::updateOrCreate(
                ['triagem_id' => $triagem->id],
                [
                    'pressao_arterial' => $request->pressao_arterial,
                    'peso' => $request->peso,
                    'altura' => $request->altura,
                    'frequencia_cardiaca' => $request->frequencia_cardiaca,
                    'temperatura' => $request->temperatura,
                    'oximetria' => $request->oximetria,
                    'glicemia_capilar' => $request->glicemia_capilar,
                ]
            );
            
            // Calculate IMC automatically
            $sinaisVitais->calcularIMC();
            $sinaisVitais->save();
            
            // Mark triage as completed
            $triagem->concluirTriagem();
            
            // Notify patient-service about completion
            $this->patientService->atualizarStatusSolicitacaoTriagem(
                $triagem->triagem_id,
                'triagem_concluida'
            );
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Triagem realizada com sucesso',
                'data' => [
                    'triagem' => $triagem->fresh(),
                    'sinais_vitais' => $sinaisVitais->fresh(),
                ]
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao realizar triagem: ' . $e->getMessage(), [
                'triagem_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao realizar triagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update triage vital signs
     * Matches: handleEditTriagem from TriagemPaciente.jsx
     * PUT /api/triagens/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pressao_arterial' => 'required|string|regex:/^\d{3}\/\d{2}$/',
            'peso' => 'required|numeric|min:0',
            'altura' => 'required|numeric|min:0',
            'frequencia_cardiaca' => 'required|integer|min:0',
            'temperatura' => 'required|numeric|min:30|max:45',
            'oximetria' => 'nullable|integer|min:0|max:100',
            'glicemia_capilar' => 'nullable|integer|min:0',
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
            
            $triagem = Triagem::with('sinaisVitais')->findOrFail($id);
            
            if ($triagem->status !== 'triagem_concluida') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Apenas triagens concluídas podem ser editadas'
                ], 400);
            }
            
            // Update vital signs
            if ($triagem->sinaisVitais) {
                $triagem->sinaisVitais->update([
                    'pressao_arterial' => $request->pressao_arterial,
                    'peso' => $request->peso,
                    'altura' => $request->altura,
                    'frequencia_cardiaca' => $request->frequencia_cardiaca,
                    'temperatura' => $request->temperatura,
                    'oximetria' => $request->oximetria,
                    'glicemia_capilar' => $request->glicemia_capilar,
                ]);
                
                // Recalculate IMC
                $triagem->sinaisVitais->calcularIMC();
                $triagem->sinaisVitais->save();
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Triagem atualizada com sucesso',
                'data' => [
                    'triagem' => $triagem->fresh(),
                    'sinais_vitais' => $triagem->sinaisVitais->fresh(),
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao atualizar triagem: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar triagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get triage details with vital signs
     * GET /api/triagens/{id}
     */
    public function show($id)
    {
        try {
            $triagem = Triagem::with(['sinaisVitais', 'agendamentoConsulta'])
                              ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $triagem
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Triagem não encontrada'
            ], 404);
        }
    }
    
    /**
     * Update triage status
     * PATCH /api/triagens/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:aguardando_triagem,em_triagem,triagem_concluida,cancelada',
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
            
            $triagem = Triagem::findOrFail($id);
            
            // Update status
            $triagem->update(['status' => $request->status]);
            
            // If status is triagem_concluida, mark as completed
            if ($request->status === 'triagem_concluida') {
                $triagem->concluirTriagem();
            }
            
            // Notify patient-service
            if ($triagem->triagem_id) {
                $this->patientService->atualizarStatusSolicitacaoTriagem(
                    $triagem->triagem_id,
                    $request->status
                );
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Status da triagem atualizado com sucesso',
                'data' => $triagem->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar status da triagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cancel triage
     * DELETE /api/triagens/{id}
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $triagem = Triagem::findOrFail($id);
            
            if ($triagem->consulta_agendada) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Não é possível cancelar triagem com consulta agendada'
                ], 400);
            }
            
            $triagem->cancelar('Cancelamento manual');
            
            // Notify patient-service
            if ($triagem->triagem_id) {
                $this->patientService->atualizarStatusSolicitacaoTriagem(
                    $triagem->triagem_id,
                    'cancelada'
                );
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Triagem cancelada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao cancelar triagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get triage statistics
     * GET /api/triagens/estatisticas
     */
    public function estatisticas(Request $request)
    {
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        
        $stats = Triagem::getEstatisticas($dataInicio, $dataFim);
        
        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
