<?php
// filepath: services/triage-service/app/Http/Controllers/Api/SinaisVitaisController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SinaisVitais;
use App\Models\Triagem;
use App\Services\PatientServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SinaisVitaisController extends Controller
{
    protected $patientService;
    
    public function __construct(PatientServiceClient $patientService)
    {
        $this->patientService = $patientService;
    }
    
    /**
     * List all vital signs with filters
     * GET /api/sinais-vitais
     */
    public function index(Request $request)
    {
        try {
            $query = SinaisVitais::with('triagem');
            
            // Filter by triage status
            if ($request->has('status_triagem')) {
                $query->whereHas('triagem', function($q) use ($request) {
                    $q->where('status', $request->status_triagem);
                });
            }
            
            // Filter by date range
            if ($request->has('data_inicio')) {
                $query->whereHas('triagem', function($q) use ($request) {
                    $q->whereDate('data_triagem', '>=', $request->data_inicio);
                });
            }
            
            if ($request->has('data_fim')) {
                $query->whereHas('triagem', function($q) use ($request) {
                    $q->whereDate('data_triagem', '<=', $request->data_fim);
                });
            }
            
            // Filter by critical state
            if ($request->has('criticos') && $request->criticos == 'true') {
                $sinaisVitais = $query->get()->filter(function($sv) {
                    return $sv->isEstadoCritico();
                })->values();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $sinaisVitais,
                    'total' => $sinaisVitais->count()
                ]);
            }
            
            $perPage = $request->get('per_page', 15);
            $sinaisVitais = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $sinaisVitais
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao listar sinais vitais: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao listar sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get vital signs by triage ID
     * GET /api/sinais-vitais/triagem/{triagemId}
     */
    public function getByTriagem($triagemId)
    {
        try {
            $sinaisVitais = SinaisVitais::where('triagem_id', $triagemId)->first();
            
            if (!$sinaisVitais) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sinais vitais não encontrados para esta triagem'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'sinais_vitais' => $sinaisVitais,
                    'summary' => $sinaisVitais->getSummary(),
                    'estado_critico' => $sinaisVitais->isEstadoCritico()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar sinais vitais: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get historical vital signs by patient NID
     * GET /api/sinais-vitais/historico/{nid}
     */
    public function getHistoricoByNid($nid)
    {
        try {
            // Buscar todas as triagens do paciente
            $triagens = Triagem::where('nid', $nid)
                ->orderBy('data_triagem', 'desc')
                ->get();
            
            if ($triagens->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Nenhuma triagem encontrada para este paciente',
                    'data' => []
                ]);
            }
            
            // Buscar sinais vitais de todas as triagens
            $triagemIds = $triagens->pluck('id');
            $sinaisVitais = SinaisVitais::whereIn('triagem_id', $triagemIds)
                ->with(['triagem:id,nid,data_triagem,estado_urgencia,codigo_triagem'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($sv) {
                    return [
                        'id' => $sv->id,
                        'triagem_id' => $sv->triagem_id,
                        'data_triagem' => $sv->triagem->data_triagem ?? null,
                        'estado_urgencia' => $sv->triagem->estado_urgencia ?? null,
                        'codigo_triagem' => $sv->triagem->codigo_triagem ?? null,
                        'temperatura' => $sv->temperatura,
                        'temperatura_status' => $sv->temperatura_status,
                        'pressao_arterial' => $sv->pressao_arterial,
                        'pressao_arterial_sistolica' => $sv->pressao_arterial_sistolica,
                        'pressao_arterial_diastolica' => $sv->pressao_arterial_diastolica,
                        'pressao_arterial_status' => $sv->pressao_arterial_status,
                        'frequencia_cardiaca' => $sv->frequencia_cardiaca,
                        'oximetria' => $sv->oximetria,
                        'oximetria_status' => $sv->oximetria_status,
                        'glicemia_capilar' => $sv->glicemia_capilar,
                        'glicemia_status' => $sv->glicemia_status,
                        'peso' => $sv->peso,
                        'altura' => $sv->altura,
                        'imc' => $sv->imc,
                        'classificacao_imc' => $sv->classificacao_imc,
                        'frequencia_respiratoria' => $sv->frequencia_respiratoria,
                        'escala_dor' => $sv->escala_dor,
                        'created_at' => $sv->created_at,
                    ];
                });
            
            return response()->json([
                'status' => 'success',
                'data' => $sinaisVitais,
                'total' => $sinaisVitais->count(),
                'nid' => $nid
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de sinais vitais por NID: ' . $e->getMessage(), [
                'nid' => $nid,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar histórico de sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create or update vital signs for a triage
     * Matches: handleFinishTriagem from TriagemPaciente.jsx
     * POST /api/sinais-vitais
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // triagem_id may be absent when frontend only has a pending solicitacao; we'll handle that below
            'triagem_id' => 'nullable|integer',
            'paciente_id' => 'nullable|integer',
            'pressao_arterial' => 'required|string|regex:/^\d{3}\/\d{2}$/',
            'peso' => 'required|numeric|min:0',
            'altura' => 'required|numeric|min:0',
            'frequencia_cardiaca' => 'required|integer|min:0',
            'temperatura' => 'required|numeric|min:30|max:45',
            'oximetria' => 'nullable|integer|min:0|max:100',
            'glicemia_capilar' => 'nullable|integer|min:0',
            'frequencia_respiratoria' => 'nullable|integer|min:0',
            'escala_dor' => 'nullable|integer|min:0|max:10',
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
            // Resolve triagem: if provided and exists, use it. Otherwise try to create triagem from pending solicitacao.
            $triagem = null;

            if ($request->filled('triagem_id')) {
                $triagem = Triagem::find($request->triagem_id);
                if (!$triagem) {
                    // will try to recover below using paciente_id
                    $triagem = null;
                }
            }

            // If triagem still null, attempt to find a pending solicitacao for the paciente and create triagem
            if (!$triagem) {
                $pacienteId = $request->get('paciente_id');
                $triagem_id_sent = $request->get('triagem_id');

                if (!$pacienteId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'triagem_id inválido e paciente_id ausente; não foi possível vincular sinais vitais a uma triagem'
                    ], 422);
                }

                $patientClient = new PatientServiceClient();
                $pendentes = $patientClient->getPendentes();

                \Log::debug('SinaisVitaisController.store() - Debug Info:', [
                    'paciente_id_requested' => $pacienteId,
                    'triagem_id_requested' => $triagem_id_sent,
                    'pendentes_count' => is_array($pendentes) ? count($pendentes) : 'not_array',
                    'pendentes_data' => is_array($pendentes) ? $pendentes : 'not_array'
                ]);

                $matching = null;
                if (is_array($pendentes)) {
                    foreach ($pendentes as $s) {
                        // accept either paciente_id key or nested paciente.id
                        $sidPaciente = $s['paciente_id'] ?? ($s['paciente']['id'] ?? null);
                        
                        \Log::debug('SinaisVitaisController.store() - Checking solicitacao:', [
                            'solicitacao_id' => $s['id'] ?? null,
                            'solicitacao_status' => $s['status'] ?? null,
                            'solicitacao_paciente_id' => $sidPaciente,
                            'matches_requested_paciente' => intval($sidPaciente) === intval($pacienteId)
                        ]);
                        
                        if ($sidPaciente && intval($sidPaciente) === intval($pacienteId)) {
                            $matching = $s;
                            break;
                        }
                    }
                }

                if (!$matching) {
                    \Log::error('SinaisVitaisController.store() - No matching solicitacao found', [
                        'paciente_id' => $pacienteId,
                        'triagem_id' => $triagem_id_sent,
                        'pendentes_count' => is_array($pendentes) ? count($pendentes) : 0
                    ]);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Não foi encontrada solicitação de triagem pendente para este paciente'
                    ], 422);
                }

                // mark solicitacao as concluida in patient-service (we'll attach triagem_id after creation)
                $solicitacaoId = $matching['id'] ?? $matching['solicitacao_id'] ?? null;

                // create triagem locally using available data from solicitacao + request
                $triagemData = [
                    'codigo_triagem' => Triagem::gerarCodigoTriagem(),
                    'paciente_id' => $pacienteId,
                    'triagem_id' => $solicitacaoId,
                    'nid' => $matching['nid'] ?? null,
                    'nome' => $matching['nome'] ?? ($matching['paciente']['nome'] ?? null),
                    'apelido' => $matching['apelido'] ?? ($matching['paciente']['apelido'] ?? null),
                    'genero' => $matching['genero'] ?? null,
                    'data_nascimento' => $matching['data_nascimento'] ?? null,
                    'data_hora_inicio' => now(),
                    'data_cadastro' => $matching['data_cadastro'] ?? now(),
                    'data_triagem' => now(),
                    'estado_urgencia' => $matching['estado_urgencia'] ?? ($request->get('estado_urgencia') ?? 'normal'),
                    'tipo_triagem' => $matching['tipo_triagem'] ?? ($request->get('tipo_triagem') ?? 'inicial'),
                    'status' => 'triagem_concluida',
                    'observacoes' => $request->get('observacoes') ?? null,
                ];

                $triagem = Triagem::create($triagemData);

                // notify patient-service that solicitacao is concluded and provide triagem id (from triage-service)
                if ($solicitacaoId) {
                    $patientClient->atualizarStatusSolicitacaoTriagem($solicitacaoId, 'triagem_concluida', $triagem->id);
                }
            }
            
            // Check if triage already has vital signs - if so, update instead of create
            $sinaisExistentes = SinaisVitais::where('triagem_id', $triagem->id)->first();
            
            if ($sinaisExistentes) {
                // Update existing vital signs
                $sinaisExistentes->update([
                    'pressao_arterial' => $request->pressao_arterial,
                    'peso' => $request->peso,
                    'altura' => $request->altura,
                    'frequencia_cardiaca' => $request->frequencia_cardiaca,
                    'temperatura' => $request->temperatura,
                    'oximetria' => $request->oximetria,
                    'glicemia_capilar' => $request->glicemia_capilar,
                    'frequencia_respiratoria' => $request->frequencia_respiratoria,
                    'escala_dor' => $request->escala_dor,
                ]);
                
                $sinaisExistentes->calcularIMC();
                $sinaisExistentes->save();
                
                $sinaisVitais = $sinaisExistentes;
            } else {
                // Create vital signs record
                $sinaisVitais = SinaisVitais::create([
                'triagem_id' => $triagem->id,
                'pressao_arterial' => $request->pressao_arterial,
                'peso' => $request->peso,
                'altura' => $request->altura,
                'frequencia_cardiaca' => $request->frequencia_cardiaca,
                'temperatura' => $request->temperatura,
                'oximetria' => $request->oximetria,
                'glicemia_capilar' => $request->glicemia_capilar,
                'frequencia_respiratoria' => $request->frequencia_respiratoria,
                'escala_dor' => $request->escala_dor,
                ]);
                
                // Calculate and save IMC automatically
                $sinaisVitais->calcularIMC();
                $sinaisVitais->save();
            }
            
            // Mark triage as completed since vital signs are filled
            $triagem->concluirTriagem();
            
            // Notify patient-service about completion
            $this->patientService->atualizarStatusSolicitacaoTriagem(
                $triagem->triagem_id,
                'triagem_concluida',
                $triagem->id
            );
            
            // Check if vital signs are critical
            if ($sinaisVitais->isEstadoCritico()) {
                Log::warning('Sinais vitais críticos detectados', [
                    'triagem_id' => $triagem->id,
                    'paciente_id' => $triagem->paciente_id,
                    'sinais_vitais' => $sinaisVitais->getSummary()
                ]);
                
                // You could trigger an alert here
                // event(new SinaisVitaisCriticosEvent($sinaisVitais, $triagem));
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sinais vitais registrados com sucesso',
                'data' => [
                    'sinais_vitais' => $sinaisVitais,
                    'imc' => $sinaisVitais->imc,
                    'classificacao_imc' => $sinaisVitais->classificacao_imc,
                    'summary' => $sinaisVitais->getSummary(),
                    'estado_critico' => $sinaisVitais->isEstadoCritico()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao registrar sinais vitais: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao registrar sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update vital signs
     * Matches: handleEditTriagem from TriagemPaciente.jsx
     * PUT /api/sinais-vitais/{id}
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
            'frequencia_respiratoria' => 'nullable|integer|min:0',
            'escala_dor' => 'nullable|integer|min:0|max:10',
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
            
            $sinaisVitais = SinaisVitais::with('triagem')->findOrFail($id);
            
            // Update vital signs
            $sinaisVitais->update([
                'pressao_arterial' => $request->pressao_arterial,
                'peso' => $request->peso,
                'altura' => $request->altura,
                'frequencia_cardiaca' => $request->frequencia_cardiaca,
                'temperatura' => $request->temperatura,
                'oximetria' => $request->oximetria,
                'glicemia_capilar' => $request->glicemia_capilar,
                'frequencia_respiratoria' => $request->frequencia_respiratoria,
                'escala_dor' => $request->escala_dor,
            ]);
            
            // Recalculate IMC with new values
            $sinaisVitais->calcularIMC();
            $sinaisVitais->save();
            
            // Mark triage as completed if not already
            if ($sinaisVitais->triagem && $sinaisVitais->triagem->status !== 'triagem_concluida') {
                $sinaisVitais->triagem->concluirTriagem();
                
                // Notify patient-service about completion
                $this->patientService->atualizarStatusSolicitacaoTriagem(
                    $sinaisVitais->triagem->triagem_id,
                    'triagem_concluida',
                    $sinaisVitais->triagem->id
                );
            }
            
            // Check if new vital signs are critical
            if ($sinaisVitais->isEstadoCritico()) {
                Log::warning('Sinais vitais atualizados para estado crítico', [
                    'triagem_id' => $sinaisVitais->triagem_id,
                    'paciente_id' => $sinaisVitais->triagem->paciente_id
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sinais vitais atualizados com sucesso',
                'data' => [
                    'sinais_vitais' => $sinaisVitais->fresh(),
                    'imc' => $sinaisVitais->imc,
                    'classificacao_imc' => $sinaisVitais->classificacao_imc,
                    'summary' => $sinaisVitais->getSummary(),
                    'estado_critico' => $sinaisVitais->isEstadoCritico()
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao atualizar sinais vitais: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get vital signs details
     * GET /api/sinais-vitais/{id}
     */
    public function show($id)
    {
        try {
            $sinaisVitais = SinaisVitais::with('triagem')->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'sinais_vitais' => $sinaisVitais,
                    'summary' => $sinaisVitais->getSummary(),
                    'estado_critico' => $sinaisVitais->isEstadoCritico(),
                    'triagem' => $sinaisVitais->triagem
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sinais vitais não encontrados'
            ], 404);
        }
    }
    
    /**
     * Calculate IMC (Body Mass Index)
     * Matches: calcularIMC from TriagemPaciente.jsx
     * POST /api/sinais-vitais/calcular-imc
     */
    public function calcularIMC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'peso' => 'required|numeric|min:0',
            'altura' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $peso = $request->peso;
            $altura = $request->altura;
            
            if ($altura <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Altura deve ser maior que zero'
                ], 400);
            }
            
            // Convert altura from cm to meters
            $alturaMetros = $altura / 100;
            $imc = round($peso / ($alturaMetros * $alturaMetros), 2);
            
            // Get IMC classification
            $classificacao = null;
            if ($imc < 18.5) {
                $classificacao = 'Abaixo do peso';
            } elseif ($imc < 25) {
                $classificacao = 'Peso normal';
            } elseif ($imc < 30) {
                $classificacao = 'Sobrepeso';
            } elseif ($imc < 35) {
                $classificacao = 'Obesidade Grau I';
            } elseif ($imc < 40) {
                $classificacao = 'Obesidade Grau II';
            } else {
                $classificacao = 'Obesidade Grau III';
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'imc' => $imc,
                    'classificacao' => $classificacao,
                    'peso' => $peso,
                    'altura' => $altura,
                    'altura_metros' => $alturaMetros
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao calcular IMC: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao calcular IMC',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get vital signs statistics
     * GET /api/sinais-vitais/estatisticas
     */
    public function estatisticas(Request $request)
    {
        try {
            $query = SinaisVitais::with('triagem');
            
            // Filter by date range
            if ($request->has('data_inicio')) {
                $query->whereHas('triagem', function($q) use ($request) {
                    $q->whereDate('data_triagem', '>=', $request->data_inicio);
                });
            }
            
            if ($request->has('data_fim')) {
                $query->whereHas('triagem', function($q) use ($request) {
                    $q->whereDate('data_triagem', '<=', $request->data_fim);
                });
            }
            
            $sinaisVitais = $query->get();
            
            $stats = [
                'total' => $sinaisVitais->count(),
                'estado_critico' => $sinaisVitais->filter(fn($sv) => $sv->isEstadoCritico())->count(),
                
                // Blood pressure statistics
                'pressao_arterial' => [
                    'normal' => $sinaisVitais->filter(fn($sv) => $sv->pressao_arterial_status === 'normal')->count(),
                    'hipertensao' => $sinaisVitais->filter(fn($sv) => in_array($sv->pressao_arterial_status, ['hipertensao_leve', 'hipertensao_moderada', 'hipertensao_grave']))->count(),
                ],
                
                // Temperature statistics
                'temperatura' => [
                    'normal' => $sinaisVitais->filter(fn($sv) => $sv->temperatura_status === 'normal')->count(),
                    'febre' => $sinaisVitais->filter(fn($sv) => in_array($sv->temperatura_status, ['febre_leve', 'febre_moderada', 'febre_alta']))->count(),
                ],
                
                // Blood glucose statistics
                'glicemia' => [
                    'normal' => $sinaisVitais->filter(fn($sv) => $sv->glicemia_status === 'normal')->count(),
                    'alterada' => $sinaisVitais->filter(fn($sv) => in_array($sv->glicemia_status, ['hipoglicemia', 'pre_diabetes', 'hiperglicemia']))->count(),
                ],
                
                // Oxygen saturation statistics
                'oximetria' => [
                    'normal' => $sinaisVitais->filter(fn($sv) => $sv->oximetria_status === 'normal')->count(),
                    'baixa' => $sinaisVitais->filter(fn($sv) => in_array($sv->oximetria_status, ['baixo', 'critico']))->count(),
                ],
                
                // IMC statistics
                'imc' => [
                    'abaixo_peso' => $sinaisVitais->filter(fn($sv) => $sv->classificacao_imc === 'Abaixo do peso')->count(),
                    'peso_normal' => $sinaisVitais->filter(fn($sv) => $sv->classificacao_imc === 'Peso normal')->count(),
                    'sobrepeso' => $sinaisVitais->filter(fn($sv) => $sv->classificacao_imc === 'Sobrepeso')->count(),
                    'obesidade' => $sinaisVitais->filter(fn($sv) => in_array($sv->classificacao_imc, ['Obesidade Grau I', 'Obesidade Grau II', 'Obesidade Grau III']))->count(),
                ],
                
                // Averages
                'medias' => [
                    'peso' => round($sinaisVitais->avg('peso'), 2),
                    'altura' => round($sinaisVitais->avg('altura'), 2),
                    'imc' => round($sinaisVitais->avg('imc'), 2),
                    'temperatura' => round($sinaisVitais->avg('temperatura'), 1),
                    'frequencia_cardiaca' => round($sinaisVitais->avg('frequencia_cardiaca')),
                    'oximetria' => round($sinaisVitais->avg('oximetria')),
                ]
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao calcular estatísticas: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao calcular estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get critical vital signs
     * GET /api/sinais-vitais/criticos
     */
    public function criticos(Request $request)
    {
        try {
            $sinaisVitaisCriticos = SinaisVitais::with(['triagem.agendamentoConsulta'])
                ->get()
                ->filter(function($sv) {
                    return $sv->isEstadoCritico();
                })
                ->map(function($sv) {
                    return [
                        'id' => $sv->id,
                        'triagem_id' => $sv->triagem_id,
                        'paciente' => [
                            'id' => $sv->triagem->paciente_id,
                            'nome' => $sv->triagem->nome,
                            'nid' => $sv->triagem->nid,
                        ],
                        'sinais_vitais' => $sv->getSummary(),
                        'data_triagem' => $sv->triagem->data_triagem,
                        'consulta_agendada' => $sv->triagem->consulta_agendada,
                    ];
                })
                ->values();
            
            return response()->json([
                'status' => 'success',
                'data' => $sinaisVitaisCriticos,
                'total' => $sinaisVitaisCriticos->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar sinais vitais críticos: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar sinais vitais críticos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update vital signs by triage ID
     * PUT /api/sinais-vitais/triagem/{triagemId}
     */
    public function updateByTriagem(Request $request, $triagemId)
    {
        $validator = Validator::make($request->all(), [
            'pressao_arterial' => 'required|string|regex:/^\d{2,3}\/\d{2}$/',
            'peso' => 'required|numeric|min:0',
            'altura' => 'required|numeric|min:0',
            'frequencia_cardiaca' => 'required|integer|min:0',
            'temperatura' => 'required|numeric|min:30|max:45',
            'oximetria' => 'nullable|integer|min:0|max:100',
            'glicemia_capilar' => 'nullable|integer|min:0',
            'frequencia_respiratoria' => 'nullable|integer|min:0',
            'escala_dor' => 'nullable|integer|min:0|max:10',
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
            
            // Find triage
            $triagem = Triagem::findOrFail($triagemId);
            
            // Find or create vital signs for this triage
            $sinaisVitais = SinaisVitais::where('triagem_id', $triagem->id)->first();
            
            if (!$sinaisVitais) {
                // Create new vital signs if they don't exist
                $sinaisVitais = SinaisVitais::create([
                    'triagem_id' => $triagem->id,
                    'pressao_arterial' => $request->pressao_arterial,
                    'peso' => $request->peso,
                    'altura' => $request->altura,
                    'frequencia_cardiaca' => $request->frequencia_cardiaca,
                    'temperatura' => $request->temperatura,
                    'oximetria' => $request->oximetria,
                    'glicemia_capilar' => $request->glicemia_capilar,
                    'frequencia_respiratoria' => $request->frequencia_respiratoria,
                    'escala_dor' => $request->escala_dor,
                ]);
            } else {
                // Update existing vital signs
                $sinaisVitais->update([
                    'pressao_arterial' => $request->pressao_arterial,
                    'peso' => $request->peso,
                    'altura' => $request->altura,
                    'frequencia_cardiaca' => $request->frequencia_cardiaca,
                    'temperatura' => $request->temperatura,
                    'oximetria' => $request->oximetria,
                    'glicemia_capilar' => $request->glicemia_capilar,
                    'frequencia_respiratoria' => $request->frequencia_respiratoria,
                    'escala_dor' => $request->escala_dor,
                ]);
            }
            
            // Recalculate IMC with new values
            $sinaisVitais->calcularIMC();
            $sinaisVitais->save();
            
            // Mark triage as completed if not already
            if ($triagem->status !== 'triagem_concluida') {
                $triagem->concluirTriagem();
                
                // Notify patient-service about completion
                $this->patientService->atualizarStatusSolicitacaoTriagem(
                    $triagem->triagem_id,
                    'triagem_concluida',
                    $triagem->id
                );
            }
            
            // Check if new vital signs are critical
            if ($sinaisVitais->isEstadoCritico()) {
                Log::warning('Sinais vitais atualizados para estado crítico', [
                    'triagem_id' => $triagem->id,
                    'paciente_id' => $triagem->paciente_id
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sinais vitais atualizados com sucesso',
                'data' => [
                    'sinais_vitais' => $sinaisVitais->fresh(),
                    'imc' => $sinaisVitais->imc,
                    'classificacao_imc' => $sinaisVitais->classificacao_imc,
                    'summary' => $sinaisVitais->getSummary(),
                    'estado_critico' => $sinaisVitais->isEstadoCritico()
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao atualizar sinais vitais por triagem: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete vital signs (soft delete)
     * DELETE /api/sinais-vitais/{id}
     */
    public function destroy($id)
    {
        try {
            $sinaisVitais = SinaisVitais::findOrFail($id);
            
            // Check if associated triage allows deletion
            if ($sinaisVitais->triagem->consulta_agendada) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Não é possível excluir sinais vitais de triagem com consulta agendada'
                ], 400);
            }
            
            $sinaisVitais->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sinais vitais excluídos com sucesso'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao excluir sinais vitais',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}