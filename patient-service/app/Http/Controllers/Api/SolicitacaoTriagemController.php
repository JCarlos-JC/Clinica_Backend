<?php
// patient-service/app/Http/Controllers/Api/SolicitacaoTriagemController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitacaoTriagem;
use App\Models\Paciente;
use App\Services\TriageServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitacaoTriagemController extends Controller
{
    protected $triageService;
    
    public function __construct(TriageServiceClient $triageService)
    {
        $this->triageService = $triageService;
    }
    
    /**
     * Create triage request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|exists:pacientes,id',
            'triagem_id' => 'nullable|integer',
            'urgencia' => 'required|in:emergencia,urgente,normal',
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
            
            $paciente = Paciente::findOrFail($request->paciente_id);
            
            // Check if patient already has pending triage
            $solicitacaoExistente = SolicitacaoTriagem::where('paciente_id', $request->paciente_id)
                                                      ->whereIn('status', ['aguardando_triagem', 'em_triagem'])
                                                      ->first();
            
            if ($solicitacaoExistente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paciente já possui solicitação de triagem pendente'
                ], 409);
            }
            

            
            // Create triage request in patient-service
            $solicitacao = SolicitacaoTriagem::create([
                'paciente_id' => $request->paciente_id,
                // data_triagem is required by the schema; set now() on creation
                // (will be adjusted later if triage occurs at a different time)
                'data_triagem' => now(),
                'urgencia' => $request->urgencia,
                'status' => 'aguardando_triagem',
                'data_solicitacao' => now(),
                'observacoes' => $request->observacoes,
            ]);
            
            // Notify triage-service to create triage record
            $triagem = $this->triageService->criarTriagem([
                'paciente_id' => $paciente->id,
                'triagem_id' => $solicitacao->id,
                'nid' => $paciente->nid,
                'nome' => $paciente->nome,
                'apelido' => $paciente->apelido,
                'genero' => $paciente->genero,
                'data_nascimento' => $paciente->data_nascimento,
                'estado_urgencia' => $request->urgencia,
                'tipo_utente' => $paciente->tipo_utente,
                'observacoes' => $request->observacoes,
            ]);
            
            if ($triagem) {
                // Update with triage-service ID
                $solicitacao->update([
                    'triagem_id' => $triagem['id'],
                ]);
            }
            
            // Update patient status
            $paciente->update(['status' => 'aguardando_triagem']);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Solicitação de triagem registrada com sucesso',
                'data' => [
                    'solicitacao' => $solicitacao,
                    'triagem' => $triagem,
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao solicitar triagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update triage request status (called by triage-service)
     */
    public function atualizarStatus(Request $request, $id)
    {
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:aguardando_triagem,triagem_concluida',
        'triagem_id' => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $solicitacao = SolicitacaoTriagem::with('paciente')->findOrFail($id);

        $updateData = [
            'status' => $request->status
        ];

        // Quando a triagem for concluída (sinais vitais preenchidos)
        if ($request->status === 'triagem_concluida') {
            if (!$solicitacao->data_conclusao_triagem) {
                $updateData['data_conclusao_triagem'] = now();
            }
        }

        // Vinculação com o triage-service
        if ($request->filled('triagem_id')) {
            $updateData['triagem_id'] = $request->triagem_id;
        }

        $solicitacao->update($updateData);

        // (Opcional) Atualizar status do paciente
        if ($solicitacao->paciente) {
            $solicitacao->paciente->update([
                'status' => $request->status
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status da solicitação atualizado com sucesso',
            'data' => $solicitacao->fresh()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erro ao atualizar status da solicitação',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    
    /**
     * Get pending triage requests
     */
    public function aguardando(Request $request)
    {
        $solicitacoes = SolicitacaoTriagem::with('paciente')
                                          ->aguardando()
                                          ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $solicitacoes
        ]);
    }
    
    /**
     * List all triage requests
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $urgencia = $request->get('urgencia');
        $search = $request->get('search');
        
        $query = SolicitacaoTriagem::with('paciente');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($urgencia) {
            $query->where('urgencia', $urgencia);
        }
        
        if ($search) {
            $query->whereHas('paciente', function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('nid', 'like', "%{$search}%");
            });
        }
        
        $solicitacoes = $query->orderBy('created_at', 'desc')
                              ->paginate($perPage);
        
        return response()->json($solicitacoes);
    }
    
    /**
     * Show specific triage request
     */
    public function show($id)
    {
        try {
            $solicitacao = SolicitacaoTriagem::with('paciente')->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $solicitacao
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Solicitação não encontrada'
            ], 404);
        }
    }
    
    /**
     * Update triage request
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'urgencia' => 'sometimes|in:emergencia,urgente,normal',
            'observacoes' => 'nullable|string',
            'ja_consultado' => 'nullable|boolean',
            'status' => 'nullable|in:aguardando_triagem,triagem_concluida',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $solicitacao = SolicitacaoTriagem::findOrFail($id);
            $solicitacao->update($request->only(['urgencia', 'observacoes', 'ja_consultado', 'status']));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Solicitação atualizada com sucesso',
                'data' => $solicitacao->freh('paciente')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar solicitação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete triage request
     */
    public function destroy($id)
    {
        try {
            $solicitacao = SolicitacaoTriagem::findOrFail($id);
            
            if (in_array($solicitacao->status, ['em_triagem', 'triagem_concluida'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Não é possível excluir solicitação em triagem ou concluída'
                ], 400);
            }
            
            $solicitacao->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Solicitação excluída com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao excluir solicitação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Restore deleted triage request
     */
    public function restore($id)
    {
        try {
            $solicitacao = SolicitacaoTriagem::withTrashed()->findOrFail($id);
            $solicitacao->restore();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Solicitação restaurada com sucesso',
                'data' => $solicitacao
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao restaurar solicitação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get statistics
     */
    public function statistics()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => SolicitacaoTriagem::count(),
                'aguardando' => SolicitacaoTriagem::where('status', 'aguardando_triagem')->count(),
                'concluida' => SolicitacaoTriagem::where('status', 'triagem_concluida')->count(),
                'por_urgencia' => [
                    'emergencia' => SolicitacaoTriagem::where('urgencia', 'emergencia')->count(),
                    'urgente' => SolicitacaoTriagem::where('urgencia', 'urgente')->count(),
                    'normal' => SolicitacaoTriagem::where('urgencia', 'normal')->count(),
                ]
            ]
        ]);
    }
    
    /**
     * Get by patient
     */
    public function byPaciente($pacienteId)
    {
        $solicitacoes = SolicitacaoTriagem::where('paciente_id', $pacienteId)
                                          ->orderBy('created_at', 'desc')
                                          ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $solicitacoes
        ]);
    }
    
    /**
     * Get pending requests
     */
    public function getPendentes()
    {
        $solicitacoes = SolicitacaoTriagem::with('paciente')
                                          ->whereIn('status', ['aguardando_triagem', 'em_triagem'])
                                          ->orderBy('urgencia')
                                          ->orderBy('created_at', 'asc')
                                          ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $solicitacoes
        ]);
    }
    
    /**
     * Change status
     */
public function changeStatus(Request $request, $id)
{
    return $this->atualizarStatus($request, $id);
}

    
    /**
     * Cancel triage request
     */
    public function cancelar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motivo_cancelamento' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $solicitacao = SolicitacaoTriagem::findOrFail($id);
            
            $solicitacao->update([
                'status' => 'cancelada',
                'motivo_cancelamento' => $request->motivo_cancelamento,
                'data_cancelamento' => now()
            ]);
            
            // Update patient status
            $solicitacao->paciente->update(['status' => 'ativo']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Solicitação cancelada com sucesso',
                'data' => $solicitacao
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao cancelar solicitação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark as attended
     */
    public function marcarComoAtendido($id)
    {
        try {
            $solicitacao = SolicitacaoTriagem::findOrFail($id);
            
            $solicitacao->update([
                'status' => 'triagem_concluida',
                'data_conclusao_triagem' => now()
            ]);
            
            // Update patient status
            $solicitacao->paciente->update(['status' => 'triagem_concluida']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Triagem marcada como concluída',
                'data' => $solicitacao
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao marcar como atendido',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
