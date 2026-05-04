<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Parente;
use App\Models\Triagem;
use App\Models\HistoricoPaciente;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PacienteController extends Controller
{
    protected ConfigurationService $configService;

    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }
    /**
     * Display a listing of patients.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Paciente::query();

            // Filtros
            if ($request->has('status')) {
                $query->status($request->status);
            }

            if ($request->has('genero')) {
                $query->genero($request->genero);
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('tipo_utente_id')) {
                $query->where('tipo_utente_id', $request->tipo_utente_id);
            }

            // Relacionamentos
            if ($request->has('with')) {
                $relations = explode(',', $request->with);
                $query->with($relations);
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginação
            $perPage = $request->get('per_page', 15);
            
            if ($request->has('paginate') && $request->paginate === 'false') {
                $pacientes = $query->get();
                
                // Enriquecer dados com informações de localização
                $enrichedData = $pacientes->map(function ($paciente) {
                    return $this->enrichPacienteData($paciente->toArray());
                })->toArray();
                
                return response()->json([
                    'success' => true,
                    'data' => $enrichedData,
                    'total' => $pacientes->count(),
                ]);
            }

            $pacientes = $query->paginate($perPage);

            // Enriquecer dados com informações de localização
            $enrichedData = collect($pacientes->items())->map(function ($paciente) {
                return $this->enrichPacienteData($paciente->toArray());
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $enrichedData,
                'meta' => [
                    'current_page' => $pacientes->currentPage(),
                    'last_page' => $pacientes->lastPage(),
                    'per_page' => $pacientes->perPage(),
                    'total' => $pacientes->total(),
                    'from' => $pacientes->firstItem(),
                    'to' => $pacientes->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar pacientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created patient.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Log dos dados recebidos do frontend para debug
            Log::info('📥 Dados recebidos do frontend:', $request->all());
            
            // Mapear dados do frontend para formato do backend
            $mappedData = $this->mapFrontendDataToBackend($request->all());
            
            // Log dos dados mapeados
            Log::info('🔄 Dados após mapeamento:', $mappedData);
            
            $validator = Validator::make($mappedData, [
                'nome' => 'required|string|max:255',
                'apelido' => 'required|string|max:255',
                'data_nascimento' => 'required|date|before:today',
                'genero' => 'required|in:masculino,feminino,outro',
                'celular' => 'required|string|max:20',
                'celular_alternativo' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'estado_civil' => 'nullable|in:solteiro,casado,divorciado,viuvo',
                'nacionalidade' => 'nullable|string|max:255',
                
                // NID personalizado opcional
                'nid' => [
                    'nullable',
                    'string',
                    'regex:/^\d{4}\/\d{4}$/', // Formato XXXX/YYYY
                    'unique:pacientes,nid',    // Deve ser único
                ],
                
                // IDs de referências externas
                'tipo_utente_id' => 'nullable|integer',
                'unidade_organica_id' => 'nullable|integer',
                'provincia_id' => 'nullable|integer',
                'distrito_id' => 'nullable|integer',
                'bairro_id' => 'nullable|integer',
                'tipo_documento_id' => 'nullable|integer',
                'raca_id' => 'nullable|integer',
                
                // Documento de identificação
                'bilhete_identidade' => 'nullable|string|max:50',
                'documento' => 'nullable|string|max:255',
                
                // Endereço
                'avenida_rua_celula' => 'nullable|string|max:255',
                'numero_casa' => 'nullable|string|max:50',
                'quarteirao' => 'nullable|string|max:50',
                
                // Outros
                'nome_familiar' => 'nullable|string|max:255',
                'unidade_organica_familiar' => 'nullable|integer',
                'observacoes' => 'nullable|string',
            ], [
                // Mensagens de validação personalizadas para NID
                'nid.regex' => 'O NID deve seguir o formato XXXX/YYYY (exemplo: 0030/2025)',
                'nid.unique' => 'Este NID já existe. Por favor, escolha outro número.',
            ]);

            if ($validator->fails()) {
                Log::error('❌ Erro de validação:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validar referências externas no configuration-service
            Log::info('🔍 Validando referências externas...');
            $externalErrors = $this->configService->validateExternalReferences($mappedData);
            
            if (!empty($externalErrors)) {
                Log::error('❌ Erro de validação de referências externas:', $externalErrors);
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação de referências externas',
                    'errors' => $externalErrors,
                ], 422);
            }

            DB::beginTransaction();

            $paciente = Paciente::create($validator->validated());
            
            Log::info('✅ Paciente criado com sucesso:', ['id' => $paciente->id, 'nid' => $paciente->nid]);

            // Processar parentes se fornecidos
            if (!empty($request->input('parentes'))) {
                Log::info('👨‍👩‍👧‍👦 Processando parentes:', $request->input('parentes'));
                $this->processParentes($paciente->nid, $request->input('parentes'));
            }

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'criacao',
            //     dadosNovos: $paciente->toArray(),
            //     observacao: 'Paciente criado no sistema'
            // );

            DB::commit();

            // Enriquecer dados com informações de localização
            $pacienteData = $this->enrichPacienteData($paciente->load(['parentes'])->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Paciente criado com sucesso',
                'data' => $pacienteData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Erro ao criar paciente:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar paciente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified patient.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $paciente = Paciente::with([
                'parentes',
                'solicitacoesExames',
                'historicoPaciente' => function ($query) {
                    $query->latest()->take(10);
                }
            ])->findOrFail($id);

            // Enriquecer dados com informações de localização
            $pacienteData = $this->enrichPacienteData($paciente->toArray());

            return response()->json([
                'success' => true,
                'data' => $pacienteData,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar paciente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get contact data of a patient (email, phone, address)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getDadosContato(int $id): JsonResponse
    {
        try {
            $paciente = Paciente::select([
                'id',
                'nid',
                'email',
                'telefone',
                'telefone_alternativo',
                'endereco',
                'bairro_id',
                'distrito_id',
                'provincia_id'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $paciente->id,
                    'nid' => $paciente->nid,
                    'email' => $paciente->email,
                    'telefone' => $paciente->telefone,
                    'telefone_alternativo' => $paciente->telefone_alternativo,
                    'endereco' => $paciente->endereco,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar dados de contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified patient.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($id);
            
            // Log dos dados recebidos para debug
            Log::info('📥 Dados recebidos para atualização:', $request->all());
            
            // Mapear e limpar dados vindos do frontend
            $data = $this->mapFrontendDataToBackend($request->all());
            Log::info('📄 Dados após mapeamento:', $data);
            
            $validator = Validator::make($data, [
                'nome' => 'sometimes|required|string|max:255',
                'apelido' => 'sometimes|required|string|max:255',
                'data_nascimento' => 'sometimes|required|date|before:today',
                'genero' => 'sometimes|required|in:masculino,feminino,outro',
                'celular' => 'sometimes|required|string|max:20',
                'celular_alternativo' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'estado_civil' => 'nullable|in:solteiro,casado,divorciado,viuvo',
                'nacionalidade' => 'nullable|string|max:255',
                'status' => 'nullable|in:ativo,inativo,alta,obito,transferencia,transferido_especialidade,em_consulta,aguardando_triagem',
                
                // IDs de referências externas
                'tipo_utente_id' => 'nullable|integer',
                'unidade_organica_id' => 'nullable|integer',
                'provincia_id' => 'nullable|integer',
                'distrito_id' => 'nullable|integer',
                'bairro_id' => 'nullable|integer',
                'tipo_documento_id' => 'nullable|integer',
                'raca_id' => 'nullable|integer',
                
                // Documento de identificação
                'bilhete_identidade' => 'nullable|string|max:20',
                'documento' => 'nullable|string|max:255',
                
                // Endereço
                'avenida_rua_celula' => 'nullable|string|max:255',
                'numero_casa' => 'nullable|string|max:50',
                'quarteirao' => 'nullable|string|max:50',
                
                // Outros
                'nome_familiar' => 'nullable|string|max:255',
                'unidade_organica_familiar' => 'nullable|integer',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::error('❌ Erro de validação:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validar referências externas no configuration-service (apenas se fornecidas e não nulas)
            Log::info('🔍 Validando referências externas...');
            $dataToValidate = array_filter(
                array_intersect_key($data, array_flip([
                    'tipo_utente_id', 'unidade_organica_id', 'provincia_id', 
                    'distrito_id', 'bairro_id', 'tipo_documento_id', 'raca_id'
                ])), 
                function($value) {
                    return $value !== null && $value !== '' && $value > 0;
                }
            );
            
            Log::info('📋 Dados para validar:', $dataToValidate);
            
            if (!empty($dataToValidate)) {
                try {
                    $externalErrors = $this->configService->validateExternalReferences($dataToValidate);
                    
                    Log::info('🔎 Resultado da validação:', ['errors' => $externalErrors, 'empty' => empty($externalErrors)]);
                    
                    if (!empty($externalErrors)) {
                        Log::error('❌ Erro de validação de referências externas:', $externalErrors);
                        return response()->json([
                            'success' => false,
                            'message' => 'Alguns dados de referência não são válidos. Verifique os campos selecionados.',
                            'errors' => $externalErrors,
                        ], 422);
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Erro na validação de referências externas, continuando sem validação:', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('🚀 Iniciando transação de banco de dados...');
            DB::beginTransaction();

            $dadosAnteriores = $paciente->toArray();
            
            // Usar apenas os dados validados
            $dadosValidados = $validator->validated();
            Log::info('💾 Atualizando paciente com dados:', $dadosValidados);
            
            $paciente->update($dadosValidados);
            
            Log::info('✅ Paciente atualizado no banco de dados');

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'atualizacao',
            //     dadosAnteriores: $dadosAnteriores,
            //     dadosNovos: $paciente->toArray(),
            //     observacao: 'Dados do paciente atualizados'
            // );

            DB::commit();
            
            Log::info('✅ Transação commitada com sucesso');

            // Enriquecer dados com informações de localização
            $pacienteData = $this->enrichPacienteData($paciente->fresh(['parentes'])->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Paciente atualizado com sucesso',
                'data' => $pacienteData,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar paciente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified patient (soft delete).
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($id);

            DB::beginTransaction();

            $dadosAnteriores = $paciente->toArray();
            
            $paciente->delete();

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'exclusao',
            //     dadosAnteriores: $dadosAnteriores,
            //     observacao: 'Paciente excluído do sistema (soft delete)'
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paciente excluído com sucesso',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir paciente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a soft deleted patient.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $paciente = Paciente::onlyTrashed()->findOrFail($id);

            DB::beginTransaction();

            $paciente->restore();

            // // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'restauracao',
            //     dadosNovos: $paciente->toArray(),
            //     observacao: 'Paciente restaurado no sistema'
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paciente restaurado com sucesso',
                'data' => $paciente,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar paciente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get patient statistics.
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => Paciente::count(),
                'ativos' => Paciente::ativo()->count(),
                'inativos' => Paciente::inativo()->count(),
                'por_genero' => [
                    'masculino' => Paciente::genero('masculino')->count(),
                    'feminino' => Paciente::genero('feminino')->count(),
                    'outro' => Paciente::genero('outro')->count(),
                ],
                'por_status' => Paciente::select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->get(),
                'criados_hoje' => Paciente::whereDate('created_at', today())->count(),
                'criados_esta_semana' => Paciente::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'criados_este_mes' => Paciente::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change patient status.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:ativo,inativo,alta,obito,transferencia,transferido_especialidade,em_consulta,aguardando_triagem',
                'observacao' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paciente = Paciente::findOrFail($id);

            DB::beginTransaction();

            $statusAnterior = $paciente->status;
            $paciente->status = $request->status;
            $paciente->save();

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: match($request->status) {
            //         'alta' => 'alta',
            //         'obito' => 'obito',
            //         'transferencia', 'transferido_especialidade' => 'transferencia',
            //         default => 'atualizacao',
            //     },
            //     dadosAnteriores: ['status' => $statusAnterior],
            //     dadosNovos: ['status' => $request->status],
            //     observacao: $request->observacao ?? "Status alterado de {$statusAnterior} para {$request->status}"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status do paciente alterado com sucesso',
                'data' => $paciente,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get next available NID.
     * 
     * @return JsonResponse
     */
    public function nextNID(): JsonResponse
    {
        try {
            $nextNID = Paciente::proximoNID();

            return response()->json([
                'success' => true,
                'data' => [
                    'next_nid' => $nextNID,
                    'year' => now()->year,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar próximo NID',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tipos de utentes
     * 
     * @return JsonResponse
     */
    public function getTiposUtentes(): JsonResponse
    {
        try {
            $tiposUtentes = $this->configService->getTiposUtentes();

            return response()->json([
                'success' => true,
                'data' => $tiposUtentes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de utentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get províncias
     * 
     * @return JsonResponse
     */
    public function getProvincias(): JsonResponse
    {
        try {
            $provincias = $this->configService->getProvincias();

            return response()->json([
                'success' => true,
                'data' => $provincias,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar províncias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get distritos by província
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDistritos(Request $request): JsonResponse
    {
        try {
            if ($request->has('provincia_id') && !empty($request->provincia_id)) {
                $distritos = $this->configService->getDistritosByProvincia($request->provincia_id);
            } else {
                $distritos = $this->configService->getAllDistritos();
            }

            return response()->json([
                'success' => true,
                'data' => $distritos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar distritos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bairros by distrito
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBairros(Request $request): JsonResponse
    {
        try {
            if ($request->has('distrito_id') && !empty($request->distrito_id)) {
                $bairros = $this->configService->getBairrosByDistrito($request->distrito_id);
            } else {
                $bairros = $this->configService->getAllBairros();
            }

            return response()->json([
                'success' => true,
                'data' => $bairros,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar bairros',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tipos de documentos
     * 
     * @return JsonResponse
     */
    public function getTiposDocumentos(): JsonResponse
    {
        try {
            $tiposDocumentos = $this->configService->getTiposDocumentos();

            return response()->json([
                'success' => true,
                'data' => $tiposDocumentos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de documentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get raças
     * 
     * @return JsonResponse
     */
    public function getRacas(): JsonResponse
    {
        try {
            $racas = $this->configService->getRacas();

            return response()->json([
                'success' => true,
                'data' => $racas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar raças',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unidades orgânicas
     * 
     * @return JsonResponse
     */
    public function getUnidadesOrganicas(): JsonResponse
    {
        try {
            $unidadesOrganicas = $this->configService->getUnidadesOrganicas();

            return response()->json([
                'success' => true,
                'data' => $unidadesOrganicas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar unidades orgânicas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get graus de parentesco
     * 
     * @return JsonResponse
     */
    public function getGrausParentesco(): JsonResponse
    {
        try {
            $grausParentesco = $this->configService->getGrausParentesco();

            return response()->json([
                'success' => true,
                'data' => $grausParentesco,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar graus de parentesco',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get métodos de pagamento
     * 
     * @return JsonResponse
     */
    public function getMetodosPagamento(): JsonResponse
    {
        try {
            $metodosPagamento = $this->configService->getMetodosPagamento();

            return response()->json([
                'success' => true,
                'data' => $metodosPagamento,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar métodos de pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tipos de consulta
     * 
     * @return JsonResponse
     */
    public function getTiposConsulta(): JsonResponse
    {
        try {
            $tiposConsulta = $this->configService->getTiposConsulta();

            return response()->json([
                'success' => true,
                'data' => $tiposConsulta,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de consulta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar paciente por NID
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function findByNID(Request $request): JsonResponse
    {
        if (!$request->filled('nid')) {
            return response()->json([
                'success' => false,
                'message' => 'NID não informado'
            ], 400);
        }
        
        try {
            $paciente = Paciente::with('parentes')->where('nid', $request->nid)->firstOrFail();
            
            return response()->json([
                'success' => true,
                'data' => $paciente
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado com este NID',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Obter dados necessários para o formulário de pagamento da consulta regular
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function getDadosPagamento($id): JsonResponse
    {
        try {
            // Buscar por ID ou NID - melhorar lógica de detecção
            $paciente = null;
            
            // Se contém '/' ou não é puramente numérico, tratar como NID
            if (str_contains($id, '/') || !ctype_digit(strval($id))) {
                $paciente = Paciente::where('nid', $id)->firstOrFail();
                Log::info('🔍 Buscando paciente por NID:', ['nid' => $id]);
            } else {
                $paciente = Paciente::findOrFail($id);
                Log::info('🔍 Buscando paciente por ID:', ['id' => $id]);
            }

            // Enriquecer dados do paciente
            $pacienteEnriquecido = $this->enrichPacienteData($paciente->toArray());

            // Buscar dados de configuração
            $tipoUtente = $this->configService->getTipoUtente($paciente->tipo_utente_id);
            $metodosPagamento = $this->configService->getMetodosPagamento();
            
            // Verificar se é estudante bolseiro
            $isEstudanteBolseiro = isset($tipoUtente['codigo']) && 
                strtolower($tipoUtente['codigo']) === 'est-b';

            // Buscar todas as consultas disponíveis para este tipo de utente na tabela preco_consultas
            $consultasDisponiveis = [];
            try {
                $consultasDisponiveis = $this->configService->getConsultasDisponiveisParaUtente($paciente->tipo_utente_id);
            } catch (\Exception $e) {
                Log::error('❌ Erro ao buscar consultas disponíveis:', [
                    'tipo_utente_id' => $paciente->tipo_utente_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Usar a primeira consulta disponível ou buscar uma consulta padrão
            $consultaEscolhida = null;
            $valorPadrao = 0;
            $valorConsultaInfo = null;
            
            if (!empty($consultasDisponiveis)) {
                // Preferir "Consulta Geral" se disponível, senão usar a primeira disponível
                $consultaEscolhida = collect($consultasDisponiveis)->first(function($consulta) {
                    return str_contains(strtolower($consulta['tipo_consulta']['nome'] ?? ''), 'geral');
                }) ?? $consultasDisponiveis[0];
                
                $valorConsultaInfo = [
                    'valor' => $consultaEscolhida['valor'],
                    'descricao' => $consultaEscolhida['descricao'],
                    'tipo_consulta' => $consultaEscolhida['tipo_consulta'],
                    'tipo_utente' => $consultaEscolhida['tipo_utente']
                ];
                
                $valorPadrao = $isEstudanteBolseiro ? 0 : (float)$consultaEscolhida['valor'];
                
                Log::info('💰 Consulta e valor obtidos da tabela preco_consultas:', [
                    'tipo_consulta_id' => $consultaEscolhida['tipo_consulta']['id'],
                    'tipo_consulta_nome' => $consultaEscolhida['tipo_consulta']['nome'],
                    'tipo_utente_id' => $paciente->tipo_utente_id,
                    'valor_original' => $consultaEscolhida['valor'],
                    'valor_final' => $valorPadrao,
                    'is_estudante_bolseiro' => $isEstudanteBolseiro
                ]);
            } else {
                // Fallback: Criar consulta padrão quando não há dados na tabela preco_consultas
                Log::warning('⚠️ Criando consulta padrão - nenhuma encontrada na tabela preco_consultas:', [
                    'tipo_utente_id' => $paciente->tipo_utente_id
                ]);
                
                $valorPadrao = $isEstudanteBolseiro ? 0 : 500; // Valor padrão
                $consultaEscolhida = [
                    'valor' => $valorPadrao,
                    'descricao' => 'Consulta Geral (Padrão)',
                    'tipo_consulta' => [
                        'id' => 1,
                        'nome' => 'Consulta Geral'
                    ],
                    'tipo_utente' => $tipoUtente
                ];
                
                $valorConsultaInfo = [
                    'valor' => $valorPadrao,
                    'descricao' => 'Consulta Geral (Valor Padrão)',
                    'tipo_consulta' => $consultaEscolhida['tipo_consulta'],
                    'tipo_utente' => $tipoUtente
                ];
                
                $consultasDisponiveis = [$consultaEscolhida];
            }

            // Mapear métodos de pagamento para formato do frontend
            $metodosPagamentoMapeados = collect($metodosPagamento)->map(function ($metodo) {
                return [
                    'value' => strtolower($metodo['slug'] ?? $metodo['codigo'] ?? 'dinheiro'),
                    'label' => $metodo['nome'],
                    'id' => $metodo['id']
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'paciente' => [
                        'id' => $pacienteEnriquecido['id'],
                        'nid' => $pacienteEnriquecido['nid'],
                        'nome' => $pacienteEnriquecido['nome'],
                        'apelido' => $pacienteEnriquecido['apelido'],
                        'tipoUtente' => $isEstudanteBolseiro ? 'estudanteBolseiro' : 'outros',
                        'tipoUtenteNome' => $tipoUtente['nome'] ?? 'Não informado'
                    ],
                    'configuracao' => [
                        'tipoConsulta' => $consultaEscolhida['tipo_consulta']['nome'] ?? 'Não disponível',
                        'tipoConsultaId' => $consultaEscolhida['tipo_consulta']['id'] ?? null,
                        'valorPadrao' => $valorPadrao,
                        'valorPadraoFormatado' => number_format($valorPadrao, 2, ',', '.') . ' MT',
                        'isEstudanteBolseiro' => $isEstudanteBolseiro,
                        'precoConsultaInfo' => $valorConsultaInfo, // Informações completas da tabela preco_consultas
                        'consultasDisponiveis' => $consultasDisponiveis, // Todas as consultas disponíveis
                        'metodosPagamento' => $metodosPagamentoMapeados
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar dados de pagamento para paciente {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar dados de pagamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter dados de pagamento por NID (formato: numero/ano)
     * 
     * @param string $numero
     * @param string $ano
     * @return JsonResponse
     */
    public function getDadosPagamentoByNid($numero, $ano): JsonResponse
    {
        $nid = sprintf('%04d/%s', $numero, $ano);
        return $this->getDadosPagamento($nid);
    }

    /**
     * Processar pagamento por NID (formato: numero/ano)
     * 
     * @param Request $request
     * @param string $numero
     * @param string $ano
     * @return JsonResponse
     */
    public function pagarConsultaRegularByNid(Request $request, $numero, $ano): JsonResponse
    {
        $nid = sprintf('%04d/%s', $numero, $ano);
        return $this->pagarConsultaRegular($request, $nid);
    }

    /**
     * Marcar triagem por NID (formato: numero/ano)
     * 
     * @param Request $request
     * @param string $numero
     * @param string $ano
     * @return JsonResponse
     */
    public function marcarTriagemByNid(Request $request, $numero, $ano): JsonResponse
    {
        $nid = sprintf('%04d/%s', $numero, $ano);
        return $this->marcarTriagem($request, $nid);
    }

    /**
     * Buscar valor da consulta usando tabela preco_consultas
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getValorConsulta(Request $request): JsonResponse
    {
        try {
            Log::info('🔍 Recebendo dados para buscar valor da consulta:', $request->all());
            
            // Processar diferentes formatos de dados do frontend
            $requestData = $request->all();
            $tipoConsultaId = null;
            $tipoUtenteId = null;
            
            // Resolver tipo_consulta_id de diferentes formatos
            if (isset($requestData['tipo_consulta_id'])) {
                $tipoConsultaId = (int)$requestData['tipo_consulta_id'];
            } elseif (isset($requestData['tipoConsulta'])) {
                $tipoConsultaId = $this->mapTipoConsultaStringToId($requestData['tipoConsulta']);
            } elseif (isset($requestData['tipo_consulta'])) {
                $tipoConsultaId = $this->mapTipoConsultaStringToId($requestData['tipo_consulta']);
            }
            
            // Resolver tipo_utente_id de diferentes formatos
            if (isset($requestData['tipo_utente_id'])) {
                $tipoUtenteId = (int)$requestData['tipo_utente_id'];
            } elseif (isset($requestData['tipoUtenteId'])) {
                $tipoUtenteId = (int)$requestData['tipoUtenteId'];
            } elseif (isset($requestData['tipoUtente'])) {
                $tipoUtenteId = $this->mapTipoUtenteStringToId($requestData['tipoUtente']);
            } elseif (isset($requestData['tipo_utente'])) {
                $tipoUtenteId = $this->mapTipoUtenteStringToId($requestData['tipo_utente']);
            } elseif (isset($requestData['tipo_utente_codigo'])) {
                $tipoUtenteId = $this->mapTipoUtenteCodigoToId($requestData['tipo_utente_codigo']);
            }
            
            $validator = Validator::make([
                'tipo_consulta_id' => $tipoConsultaId,
                'tipo_utente_id' => $tipoUtenteId
            ], [
                'tipo_consulta_id' => 'required|integer',
                'tipo_utente_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                Log::error('❌ Erro de validação ao buscar valor da consulta:', [
                    'dados_recebidos' => $requestData,
                    'tipo_consulta_id' => $tipoConsultaId,
                    'tipo_utente_id' => $tipoUtenteId,
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Preço da consulta não configurado no sistema. Entre em contato com o administrador.',
                    'erro_tecnico' => 'Parâmetros inválidos ou não encontrados',
                    'errors' => $validator->errors(),
                    'debug' => [
                        'dados_recebidos' => $requestData,
                        'tipo_consulta_id_resolvido' => $tipoConsultaId,
                        'tipo_utente_id_resolvido' => $tipoUtenteId
                    ]
                ], 422);
            }

            $validated = $validator->validated();
            
            // Buscar informações da tabela preco_consultas
            $valorConsultaInfo = $this->configService->getValorConsulta(
                $validated['tipo_consulta_id'],
                $validated['tipo_utente_id']
            );

            if ($valorConsultaInfo) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'valor' => $valorConsultaInfo['valor'],
                        'valor_formatado' => number_format($valorConsultaInfo['valor'], 2, ',', '.') . ' MT',
                        'descricao' => $valorConsultaInfo['descricao'],
                        'tipo_consulta' => $valorConsultaInfo['tipo_consulta'],
                        'tipo_utente' => $valorConsultaInfo['tipo_utente'],
                        'fonte' => 'tabela_preco_consultas'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Valor não encontrado na tabela preco_consultas para os IDs fornecidos',
                    'data' => [
                        'tipo_consulta_id' => $validated['tipo_consulta_id'],
                        'tipo_utente_id' => $validated['tipo_utente_id']
                    ]
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error("Erro ao buscar valor da consulta: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar valor da consulta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processar pagamento global (usado pelo frontend quando não tem ID específico)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processarPagamentoGlobal(Request $request): JsonResponse
    {
        try {
            Log::info('📥 Processamento global de pagamento - dados recebidos:', $request->all());
            
            // Resolver paciente_id ou paciente_nid dos dados
            $pacienteId = null;
            
            if ($request->filled('paciente_id')) {
                $pacienteId = $request->paciente_id;
            } elseif ($request->filled('paciente_nid')) {
                $pacienteId = $request->paciente_nid;
            } elseif ($request->filled('nid')) {
                $pacienteId = $request->nid;
            } elseif ($request->filled('pacienteId')) {
                $pacienteId = $request->pacienteId;
            } else {
                Log::error('❌ Nenhum identificador de paciente fornecido:', $request->all());
                return response()->json([
                    'success' => false,
                    'message' => 'ID ou NID do paciente é obrigatório',
                    'debug' => [
                        'campos_recebidos' => array_keys($request->all()),
                        'dados_recebidos' => $request->all()
                    ]
                ], 422);
            }

            Log::info('🔍 Paciente identificado:', ['paciente_id_resolved' => $pacienteId]);

            // Mapear dados do frontend para formato esperado
            $dadosMapeados = $request->all();
            
            // Garantir que os campos necessários existam
            if (!$request->filled('metodoPagamento') && !$request->filled('metodo_pagamento_id')) {
                // Tentar mapear métodos comuns
                if ($request->filled('metodoPagamento')) {
                    $dadosMapeados['metodo_pagamento_id'] = $this->resolveMetodoPagamentoId($request->metodoPagamento);
                } else {
                    $dadosMapeados['metodo_pagamento_id'] = 1; // Dinheiro como padrão
                }
            }

            // Criar nova request com dados mapeados
            $requestMapeada = new \Illuminate\Http\Request($dadosMapeados);
            $requestMapeada->setMethod('POST');

            // Chamar o método existente
            $resultado = $this->pagarConsultaRegular($requestMapeada, $pacienteId);

            Log::info('✅ Processamento global concluído com sucesso');
            return $resultado;

        } catch (\Exception $e) {
            Log::error("❌ Erro no processamento global de pagamento: " . $e->getMessage(), [
                'dados_request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
                'error' => $e->getMessage(),
                'debug' => [
                    'dados_recebidos' => $request->all()
                ]
            ], 500);
        }
    }

    /**
     * Processar pagamento da consulta regular
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function pagarConsultaRegular(Request $request, $id): JsonResponse
    {
        try {
            Log::info('📥 Processando pagamento de consulta regular:', [
                'paciente_id' => $id,
                'dados_recebidos' => $request->all()
            ]);

            DB::beginTransaction();
            
            // Buscar por ID ou NID - melhorar lógica de detecção
            $paciente = null;
            
            // Se contém '/' ou não é puramente numérico, tratar como NID
            if (str_contains($id, '/') || !ctype_digit(strval($id))) {
                $paciente = Paciente::where('nid', $id)->firstOrFail();
                Log::info('🔍 Processando pagamento para paciente por NID:', ['nid' => $id]);
            } else {
                $paciente = Paciente::findOrFail($id);
                Log::info('🔍 Processando pagamento para paciente por ID:', ['id' => $id]);
            }

            Log::info('👤 Paciente encontrado:', [
                'id' => $paciente->id,
                'nid' => $paciente->nid,
                'nome' => $paciente->nome,
                'tipo_utente_id' => $paciente->tipo_utente_id
            ]);

            // Mapear dados do frontend para backend
            $frontendData = $request->all();
            
            // Resolver método de pagamento (pode vir como string do frontend)
            $metodoPagamentoId = null;
            if (isset($frontendData['metodoPagamento'])) {
                $metodoPagamentoId = $this->resolveMetodoPagamentoId($frontendData['metodoPagamento']);
            } elseif (isset($frontendData['metodo_pagamento'])) {
                $metodoPagamentoId = $this->resolveMetodoPagamentoId($frontendData['metodo_pagamento']);
            } elseif (isset($frontendData['metodo_pagamento_id'])) {
                $metodoPagamentoId = (int)$frontendData['metodo_pagamento_id'];
            }

            // Buscar tipo de consulta dinâmico (pode vir do frontend ou usar padrão)
            $tipoConsultaId = null;
            if (isset($frontendData['tipoConsultaId'])) {
                $tipoConsultaId = (int)$frontendData['tipoConsultaId'];
            } elseif (isset($frontendData['tipo_consulta_id'])) {
                $tipoConsultaId = (int)$frontendData['tipo_consulta_id'];
            } elseif (isset($frontendData['tipoConsulta'])) {
                $tipoConsultaId = $this->mapTipoConsultaStringToId($frontendData['tipoConsulta']);
            } elseif (isset($frontendData['tipo_consulta'])) {
                $tipoConsultaId = $this->mapTipoConsultaStringToId($frontendData['tipo_consulta']);
            } else {
                // Se não fornecido, buscar a primeira consulta disponível para este utente
                $consultasDisponiveis = $this->configService->getConsultasDisponiveisParaUtente($paciente->tipo_utente_id);
                if (!empty($consultasDisponiveis)) {
                    $consulta = collect($consultasDisponiveis)->first(function($consulta) {
                        return str_contains(strtolower($consulta['tipo_consulta']['nome'] ?? ''), 'geral');
                    }) ?? $consultasDisponiveis[0];
                    $tipoConsultaId = $consulta['tipo_consulta']['id'];
                }
            }
            
            if (!$tipoConsultaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de consulta não especificado e nenhuma consulta disponível para este tipo de utente',
                ], 400);
            }

            // Validar dados
            $validator = Validator::make([
                'tipo_consulta_id' => $tipoConsultaId,
                'metodo_pagamento_id' => $metodoPagamentoId,
                'valor' => $frontendData['valor'] ?? null
            ], [
                'tipo_consulta_id' => 'required|integer',
                'metodo_pagamento_id' => 'required|integer',
                'valor' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                Log::error('❌ Erro de validação no pagamento:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            
            // Buscar informações do tipo de utente para determinar valor
            $tipoUtente = $this->configService->getTipoUtente($paciente->tipo_utente_id);
            $isEstudanteBolseiro = isset($tipoUtente['codigo']) && 
                strtolower($tipoUtente['codigo']) === 'est-b';

            Log::info('🎓 Informações do tipo de utente:', [
                'tipo_utente' => $tipoUtente,
                'is_estudante_bolseiro' => $isEstudanteBolseiro
            ]);

            // Determinar valor da consulta usando tabela preco_consultas
            $valorFinal = 0;
            $isencaoAplicada = false;
            $valorConsultaInfo = null;

            // Sempre buscar o valor da tabela preco_consultas primeiro
            try {
                $valorConsultaInfo = $this->configService->getValorConsulta(
                    $validated['tipo_consulta_id'],
                    $paciente->tipo_utente_id
                );
                
                Log::info('📋 Informações da tabela preco_consultas:', [
                    'tipo_consulta_id' => $validated['tipo_consulta_id'],
                    'tipo_utente_id' => $paciente->tipo_utente_id,
                    'valor_info' => $valorConsultaInfo
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Erro ao buscar valor na tabela preco_consultas:', [
                    'error' => $e->getMessage(),
                    'tipo_consulta_id' => $validated['tipo_consulta_id'],
                    'tipo_utente_id' => $paciente->tipo_utente_id
                ]);
            }

            if ($isEstudanteBolseiro) {
                // Estudante bolseiro tem isenção automática
                $valorFinal = 0;
                $isencaoAplicada = true;
                Log::info('✅ Isenção aplicada para estudante bolseiro');
            } else {
                // Para outros tipos, usar valor da tabela preco_consultas
                if ($valorConsultaInfo && isset($valorConsultaInfo['valor'])) {
                    $valorFinal = (float)$valorConsultaInfo['valor'];
                    Log::info('💰 Valor obtido da tabela preco_consultas:', ['valor' => $valorFinal]);
                } else {
                            // Se não encontrou na tabela, usar valor fornecido pelo frontend ou fallback
                    if (isset($validated['valor']) && $validated['valor'] !== null) {
                        $valorFinal = (float)$validated['valor'];
                        Log::warning('⚠️ Usando valor do frontend (tabela preco_consultas não retornou dados):', ['valor' => $valorFinal]);
                    } else {
                        // Valor fallback baseado no tipo de utente
                        $valorFinal = $isEstudanteBolseiro ? 0 : 500; // Valor fallback atualizado
                        Log::warning('⚠️ Usando valor fallback (tabela preco_consultas não disponível):', [
                            'valor' => $valorFinal,
                            'tipo_utente_id' => $paciente->tipo_utente_id,
                            'is_estudante_bolseiro' => $isEstudanteBolseiro
                        ]);
                    }
                }
            }

            // Buscar informações completas para resposta
            $metodoPagamento = $this->configService->getMetodoPagamento($validated['metodo_pagamento_id']);
            $tipoConsulta = $this->configService->getTipoConsulta($validated['tipo_consulta_id']);
            
            // Registrar pagamento
            $paciente->update([
                'status_pagamento' => $isencaoAplicada ? 'isento' : 'pago',
                'data_pagamento' => now(),
                'tipo_consulta_id' => $validated['tipo_consulta_id'],
                'metodo_pagamento_id' => $validated['metodo_pagamento_id']
            ]);

            Log::info('✅ Pagamento registrado com sucesso');

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: $isencaoAplicada ? 'isencao' : 'pagamento',
            //     dadosNovos: [
            //         'tipo_consulta_id' => $validated['tipo_consulta_id'],
            //         'metodo_pagamento_id' => $validated['metodo_pagamento_id'],
            //         'valor_pago' => $valorFinal,
            //         'isencao_aplicada' => $isencaoAplicada
            //     ],
            //     observacao: $isencaoAplicada ? 
            //         "Isenção aplicada para estudante bolseiro" : 
            //         "Pagamento de consulta registrado - Valor: {$valorFinal} MT"
            // );
            
            DB::commit();
            
            // Enriquecer dados do paciente para resposta
            $pacienteEnriquecido = $this->enrichPacienteData($paciente->fresh()->toArray());
            
            return response()->json([
                'success' => true,
                'message' => $isencaoAplicada ? 
                    'Isenção aplicada com sucesso' : 
                    'Pagamento de consulta registrado com sucesso',
                'data' => [
                    'paciente' => $pacienteEnriquecido,
                    'pagamento' => [
                        'tipo_consulta_id' => $validated['tipo_consulta_id'],
                        'tipo_consulta_nome' => $tipoConsulta['nome'] ?? 'Consulta Geral',
                        'metodo_pagamento_id' => $validated['metodo_pagamento_id'],
                        'metodo_pagamento_nome' => $metodoPagamento['nome'] ?? 'Não informado',
                        'valor' => $valorFinal,
                        'valor_formatado' => number_format($valorFinal, 2, ',', '.') . ' MT',
                        'isencao_aplicada' => $isencaoAplicada,
                        'preco_consulta_info' => $valorConsultaInfo, // Dados da tabela preco_consultas
                        'fonte_valor' => $valorConsultaInfo ? 'tabela_preco_consultas' : 'fallback',
                        'data_pagamento' => now()->format('Y-m-d H:i:s'),
                        'data_pagamento_formatada' => now()->format('d/m/Y H:i:s')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Erro ao processar pagamento para paciente {$id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Verificar se preço está disponível para combinação tipo_consulta + tipo_utente
     */
    public function verificarPrecoDisponivel(Request $request): JsonResponse
    {
        try {
            $requestData = $request->all();
            $tipoConsultaId = null;
            $tipoUtenteId = null;
            
            // Resolver tipo_consulta_id
            if (isset($requestData['tipo_consulta_id'])) {
                $tipoConsultaId = (int)$requestData['tipo_consulta_id'];
            } elseif (isset($requestData['tipoConsulta'])) {
                $tipoConsultaId = $this->mapTipoConsultaStringToId($requestData['tipoConsulta']);
            } elseif (isset($requestData['tipo_consulta'])) {
                $tipoConsultaId = $this->mapTipoConsultaStringToId($requestData['tipo_consulta']);
            }
            
            // Resolver tipo_utente_id
            if (isset($requestData['tipo_utente_id'])) {
                $tipoUtenteId = (int)$requestData['tipo_utente_id'];
            } elseif (isset($requestData['tipoUtenteId'])) {
                $tipoUtenteId = (int)$requestData['tipoUtenteId'];
            } elseif (isset($requestData['tipo_utente_codigo'])) {
                $tipoUtenteId = $this->mapTipoUtenteCodigoToId($requestData['tipo_utente_codigo']);
            }
            
            if (!$tipoConsultaId || !$tipoUtenteId) {
                return response()->json([
                    'success' => false,
                    'disponivel' => false,
                    'message' => 'Parâmetros inválidos ou não fornecidos',
                    'debug' => [
                        'tipo_consulta_id' => $tipoConsultaId,
                        'tipo_utente_id' => $tipoUtenteId,
                        'params_recebidos' => $requestData
                    ]
                ]);
            }
            
            // Validar se os IDs existem realmente nos sistemas
            $tipoConsulta = $this->configService->getTipoConsulta($tipoConsultaId);
            $tipoUtente = $this->configService->getTipoUtente($tipoUtenteId);
            
            if (!$tipoConsulta || !$tipoUtente) {
                return response()->json([
                    'success' => false,
                    'disponivel' => false,
                    'message' => 'Tipo de consulta ou utente não encontrado',
                    'debug' => [
                        'tipo_consulta_existe' => !!$tipoConsulta,
                        'tipo_utente_existe' => !!$tipoUtente
                    ]
                ]);
            }
            
            // Verificar se existe preço configurado
            try {
                $valorInfo = $this->configService->getValorConsulta($tipoConsultaId, $tipoUtenteId);
                
                if (!$valorInfo || !isset($valorInfo['valor'])) {
                    return response()->json([
                        'success' => false,
                        'disponivel' => false,
                        'message' => 'Preço não configurado para esta combinação',
                        'sugestao' => 'Entre em contato com o administrador para configurar os preços'
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'disponivel' => true,
                    'valor_preview' => $valorInfo['valor'],
                    'message' => 'Preço configurado e disponível',
                    'detalhes' => [
                        'tipo_consulta' => $tipoConsulta['nome'] ?? '',
                        'tipo_utente' => $tipoUtente['nome'] ?? '',
                        'descricao' => $valorInfo['descricao'] ?? ''
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'disponivel' => false,
                    'message' => 'Preço não configurado para esta combinação',
                    'sugestao' => 'Entre em contato com o administrador para configurar os preços',
                    'erro_tecnico' => $e->getMessage()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao verificar preço disponível:', [
                'error' => $e->getMessage(),
                'params' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'disponivel' => false,
                'message' => 'Erro interno ao verificar preço'
            ], 500);
        }
    }

    /**
     * Marcar paciente para triagem
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function marcarTriagem(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Buscar por ID ou NID - melhorar lógica de detecção
            $paciente = null;
            
            // Se contém '/' ou não é puramente numérico, tratar como NID
            if (str_contains($id, '/') || !ctype_digit(strval($id))) {
                $paciente = Paciente::where('nid', $id)->firstOrFail();
                Log::info('🔍 Marcando triagem para paciente por NID:', ['nid' => $id]);
            } else {
                $paciente = Paciente::findOrFail($id);
                Log::info('🔍 Marcando triagem para paciente por ID:', ['id' => $id]);
            }
            
            // Verificar se o paciente já pagou a consulta
            if (!in_array($paciente->status_pagamento, ['pago', 'isento'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente precisa pagar a consulta antes de ser marcado para triagem'
                ], 400);
            }
            
            // Verificar se já existe triagem pendente para este paciente
            $triagemExistente = Triagem::where('paciente_nid', $paciente->nid)
                ->whereIn('status', ['pendente', 'em_atendimento'])
                ->exists();
                
            if ($triagemExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma triagem pendente/em atendimento para este paciente'
                ], 400);
            }
            
            // Validar dados
            $validator = Validator::make($request->all(), [
                'estados_urgencia_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            
            // Criar triagem
            // $triagem = SolicitacaoTriagem::create([
            //     'paciente_nid' => $paciente->nid,
            //     'estados_urgencia_id' => $validated['estados_urgencia_id'],
            //     'data_triagem' => now(),
            //     'status' => 'pendente',
            //     'ja_consultado' => false
            // ]);
            
            // Atualizar status do paciente (campo estado_atual não existe na tabela)
            // $paciente->update([
            //     'estado_atual' => 'em_triagem'
            // ]);

            // Registrar no histórico
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'triagem',
            //     dadosNovos: $triagem->toArray(),
            //     observacao: "Paciente marcado para triagem"
            // );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Paciente marcado para triagem com sucesso',
                'data' => [
                    'paciente' => $paciente,
                    // 'triagem' => $triagem
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao marcar triagem para paciente {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar triagem',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Obter estatísticas sobre pacientes
     * 
     * @return JsonResponse
     */
    public function estatisticas(): JsonResponse
    {
        try {
            // Total de pacientes
            $totalPacientes = Paciente::count();
            
            // Total por gênero
            $totalPorGenero = Paciente::selectRaw('genero, count(*) as total')
                ->groupBy('genero')
                ->get();
            
            // Total por tipo de utente
            $totalPorTipoUtente = Paciente::selectRaw('tipo_utente_id, count(*) as total')
                ->groupBy('tipo_utente_id')
                ->get();
            
            // Cadastros por mês (ano atual)
            $cadastrosPorMes = Paciente::selectRaw('MONTH(data_cadastro) as mes, count(*) as total')
                ->whereYear('data_cadastro', date('Y'))
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            // Pacientes por status de pagamento (campo estado_atual não existe)
            $porStatusPagamento = Paciente::selectRaw('status_pagamento, count(*) as total')
                ->whereNotNull('status_pagamento')
                ->groupBy('status_pagamento')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_pacientes' => $totalPacientes,
                    'por_genero' => $totalPorGenero,
                    'por_tipo_utente' => $totalPorTipoUtente,
                    'cadastros_por_mes' => $cadastrosPorMes,
                    'por_status_pagamento' => $porStatusPagamento
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map frontend data format to backend expected format
     */
    private function mapFrontendDataToBackend(array $frontendData): array
    {
        $mappedData = [];

        // Mapear campos diretos
        $directMappings = [
            'nome' => 'nome',
            'apelido' => 'apelido',
            'genero' => 'genero',
            'nacionalidade' => 'nacionalidade',
            'celular' => 'celular',
            'telefone' => 'celular', // Frontend pode enviar como telefone
            'email' => 'email',
            'observacoes' => 'observacoes',
            'status' => 'status',
            'quarteirao' => 'quarteirao',
            'documento' => 'documento', // Campo de documento adicional
            'nid' => 'nid', // NID personalizado
        ];

        foreach ($directMappings as $frontendKey => $backendKey) {
            if (isset($frontendData[$frontendKey]) && $frontendData[$frontendKey] !== null && $frontendData[$frontendKey] !== '') {
                $mappedData[$backendKey] = $frontendData[$frontendKey];
            }
        }

        // Mapear campos com transformação - suportando múltiplos formatos
        // Aceitar tanto dataNascimento (camelCase) quanto data_nascimento (snake_case)
        if (isset($frontendData['dataNascimento'])) {
            $mappedData['data_nascimento'] = $this->formatDate($frontendData['dataNascimento']);
        } elseif (isset($frontendData['data_nascimento'])) {
            $mappedData['data_nascimento'] = $this->formatDate($frontendData['data_nascimento']);
        }

        // Estado Civil
        if (isset($frontendData['estadoCivil'])) {
            $mappedData['estado_civil'] = $frontendData['estadoCivil'];
        } elseif (isset($frontendData['estado_civil'])) {
            $mappedData['estado_civil'] = $frontendData['estado_civil'];
        }

        // Celular Alternativo
        if (isset($frontendData['celularalternativo'])) {
            $mappedData['celular_alternativo'] = $frontendData['celularalternativo'];
        } elseif (isset($frontendData['celularAlternativo'])) {
            $mappedData['celular_alternativo'] = $frontendData['celularAlternativo'];
        } elseif (isset($frontendData['celular_alternativo'])) {
            $mappedData['celular_alternativo'] = $frontendData['celular_alternativo'];
        }

        // WhatsApp - múltiplos formatos possíveis
        if (isset($frontendData['whatsApp'])) {
            $mappedData['whatsapp'] = $frontendData['whatsApp'];
        } elseif (isset($frontendData['whatsapp'])) {
            $mappedData['whatsapp'] = $frontendData['whatsapp'];
        } elseif (isset($frontendData['WhatsApp'])) {
            $mappedData['whatsapp'] = $frontendData['WhatsApp'];
        }

        // Endereço
        if (isset($frontendData['avenidaRuaCelula'])) {
            $mappedData['avenida_rua_celula'] = $frontendData['avenidaRuaCelula'];
        } elseif (isset($frontendData['avenida_rua_celula'])) {
            $mappedData['avenida_rua_celula'] = $frontendData['avenida_rua_celula'];
        }

        if (isset($frontendData['numeroCasa'])) {
            $mappedData['numero_casa'] = $frontendData['numeroCasa'];
        } elseif (isset($frontendData['numero_casa'])) {
            $mappedData['numero_casa'] = $frontendData['numero_casa'];
        }

        if (isset($frontendData['quarteirao'])) {
            $mappedData['quarteirao'] = $frontendData['quarteirao'];
        }

        // Nome Familiar
        if (isset($frontendData['nomeFamiliarResponsavel'])) {
            $mappedData['nome_familiar'] = $frontendData['nomeFamiliarResponsavel'];
        } elseif (isset($frontendData['nome_familiar'])) {
            $mappedData['nome_familiar'] = $frontendData['nome_familiar'];
        }

        // Unidade Orgânica Familiar
        if (isset($frontendData['unidadeOrganicaFamiliar'])) {
            $mappedData['unidade_organica_familiar'] = is_numeric($frontendData['unidadeOrganicaFamiliar']) ? 
                (int)$frontendData['unidadeOrganicaFamiliar'] : 
                $this->resolveUnidadeOrganicaId($frontendData['unidadeOrganicaFamiliar']);
        } elseif (isset($frontendData['unidade_organica_familiar'])) {
            $mappedData['unidade_organica_familiar'] = (int)$frontendData['unidade_organica_familiar'];
        }

        // Documento adicional
        if (isset($frontendData['documento'])) {
            $mappedData['documento'] = $frontendData['documento'];
        }

        // Bilhete de Identidade - múltiplos formatos
        if (isset($frontendData['bilheteIdentidade'])) {
            $mappedData['bilhete_identidade'] = $frontendData['bilheteIdentidade'];
        } elseif (isset($frontendData['bilhete_identidade'])) {
            $mappedData['bilhete_identidade'] = $frontendData['bilhete_identidade'];
        } elseif (isset($frontendData['numeroDocumento'])) {
            $mappedData['bilhete_identidade'] = $frontendData['numeroDocumento'];
        }

        // Mapear IDs de referência
        if (isset($frontendData['raca'])) {
            $mappedData['raca_id'] = is_numeric($frontendData['raca']) ? 
                (int)$frontendData['raca'] : 
                $this->resolveRacaId($frontendData['raca']);
        } elseif (isset($frontendData['raca_id'])) {
            $mappedData['raca_id'] = (int)$frontendData['raca_id'];
        }

        if (isset($frontendData['tipoDocumento'])) {
            $mappedData['tipo_documento_id'] = is_numeric($frontendData['tipoDocumento']) ? 
                (int)$frontendData['tipoDocumento'] : 
                $this->resolveTipoDocumentoId($frontendData['tipoDocumento']);
        } elseif (isset($frontendData['tipo_documento_id'])) {
            $mappedData['tipo_documento_id'] = (int)$frontendData['tipo_documento_id'];
        }

        if (isset($frontendData['unidadeOrganica'])) {
            $mappedData['unidade_organica_id'] = is_numeric($frontendData['unidadeOrganica']) ? 
                (int)$frontendData['unidadeOrganica'] : 
                $this->resolveUnidadeOrganicaId($frontendData['unidadeOrganica']);
        } elseif (isset($frontendData['unidade_organica_id'])) {
            $mappedData['unidade_organica_id'] = (int)$frontendData['unidade_organica_id'];
        }

        // Resolver localização (província, distrito, bairro)
        if (isset($frontendData['provincia'])) {
            $mappedData['provincia_id'] = is_numeric($frontendData['provincia']) ? 
                (int)$frontendData['provincia'] : 
                $this->resolveProvinciaId($frontendData['provincia']);
        } elseif (isset($frontendData['provincia_id'])) {
            $mappedData['provincia_id'] = (int)$frontendData['provincia_id'];
        }

        if (isset($frontendData['distrito'])) {
            $mappedData['distrito_id'] = is_numeric($frontendData['distrito']) ? 
                (int)$frontendData['distrito'] : 
                $this->resolveDistritoId($frontendData['distrito']);
        } elseif (isset($frontendData['distrito_id'])) {
            $mappedData['distrito_id'] = (int)$frontendData['distrito_id'];
        }

        if (isset($frontendData['bairro'])) {
            $mappedData['bairro_id'] = is_numeric($frontendData['bairro']) ? 
                (int)$frontendData['bairro'] : 
                $this->resolveBairroId($frontendData['bairro']);
        } elseif (isset($frontendData['bairro_id'])) {
            $mappedData['bairro_id'] = (int)$frontendData['bairro_id'];
        }

        // Resolver tipo de utente - suporta múltiplos formatos
        if (isset($frontendData['tipoUtente'])) {
            $mappedData['tipo_utente_id'] = is_numeric($frontendData['tipoUtente']) ? 
                (int)$frontendData['tipoUtente'] : 
                $this->resolveTipoUtenteId($frontendData['tipoUtente']);
        } elseif (isset($frontendData['tipoUtenteId'])) {
            $mappedData['tipo_utente_id'] = is_numeric($frontendData['tipoUtenteId']) ? 
                (int)$frontendData['tipoUtenteId'] : 
                $this->resolveTipoUtenteId($frontendData['tipoUtenteId']);
        } elseif (isset($frontendData['tipo_utente_id'])) {
            $mappedData['tipo_utente_id'] = (int)$frontendData['tipo_utente_id'];
        } elseif (isset($frontendData['tipo_utente'])) {
            $mappedData['tipo_utente_id'] = is_numeric($frontendData['tipo_utente']) ? 
                (int)$frontendData['tipo_utente'] : 
                $this->resolveTipoUtenteId($frontendData['tipo_utente']);
        }

        return $mappedData;
    }
    /**
     * Process parentes data
     */
    private function processParentes(string $pacienteNid, array $parentes): void
    {
        foreach ($parentes as $parenteData) {
            Parente::create([
                'paciente_nid' => $pacienteNid,
                'nome' => $parenteData['nome'],
                'grau_parentesco_id' => $this->resolveGrauParentescoId($parenteData['grauParentesco']),
                'celular' => $parenteData['celular'],
                'celular_alternativo' => $parenteData['celularAlternativo'] ?? null,
            ]);
        }
    }

    /**
     * Resolve raça ID by name or code
     */
    private function resolveRacaId($racaValue): ?int
    {
        $racas = $this->configService->getRacas();
        
        foreach ($racas as $raca) {
            if (
                (isset($raca['nome']) && strtolower($raca['nome']) === strtolower($racaValue)) ||
                (isset($raca['codigo']) && strtolower($raca['codigo']) === strtolower($racaValue))
            ) {
                return $raca['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve tipo documento ID by name or code
     */
    private function resolveTipoDocumentoId($tipoDocValue): ?int
    {
        $tipos = $this->configService->getTiposDocumentos();
        
        foreach ($tipos as $tipo) {
            if (
                (isset($tipo['nome']) && strtolower($tipo['nome']) === strtolower($tipoDocValue)) ||
                (isset($tipo['codigo']) && strtolower($tipo['codigo']) === strtolower($tipoDocValue))
            ) {
                return $tipo['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve tipo utente ID by name or code
     */
    private function resolveTipoUtenteId($tipoUtenteValue): ?int
    {
        $tipos = $this->configService->getTiposUtentes();
        
        foreach ($tipos as $tipo) {
            if (
                (isset($tipo['nome']) && strtolower($tipo['nome']) === strtolower($tipoUtenteValue)) ||
                (isset($tipo['codigo']) && strtolower($tipo['codigo']) === strtolower($tipoUtenteValue))
            ) {
                return $tipo['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve unidade organica ID by name or code
     */
    private function resolveUnidadeOrganicaId($unidadeValue): ?int
    {
        $unidades = $this->configService->getUnidadesOrganicas();
        
        foreach ($unidades as $unidade) {
            if (
                (isset($unidade['nome']) && strtolower($unidade['nome']) === strtolower($unidadeValue)) ||
                (isset($unidade['codigo']) && strtolower($unidade['codigo']) === strtolower($unidadeValue))
            ) {
                return $unidade['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve província ID by name
     */
    private function resolveProvinciaId($provinciaValue): ?int
    {
        $provincias = $this->configService->getProvincias();
        
        foreach ($provincias as $provincia) {
            if (isset($provincia['nome']) && strtolower($provincia['nome']) === strtolower($provinciaValue)) {
                return $provincia['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve distrito ID by name
     */
    private function resolveDistritoId($distritoValue): ?int
    {
        $distritos = $this->configService->getAllDistritos();
        
        foreach ($distritos as $distrito) {
            if (isset($distrito['nome']) && strtolower($distrito['nome']) === strtolower($distritoValue)) {
                return $distrito['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve bairro ID by name
     */
    private function resolveBairroId($bairroValue): ?int
    {
        $bairros = $this->configService->getAllBairros();
        
        foreach ($bairros as $bairro) {
            if (isset($bairro['nome']) && strtolower($bairro['nome']) === strtolower($bairroValue)) {
                return $bairro['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve grau parentesco ID by name or code
     */
    private function resolveGrauParentescoId($grauValue): ?int
    {
        $graus = $this->configService->getGrausParentesco();
        
        foreach ($graus as $grau) {
            if (
                (isset($grau['nome']) && strtolower($grau['nome']) === strtolower($grauValue)) ||
                (isset($grau['codigo']) && strtolower($grau['codigo']) === strtolower($grauValue))
            ) {
                return $grau['id'];
            }
        }
        
        return null;
    }

    /**
     * Resolve método de pagamento ID by name or code
     */
    private function resolveMetodoPagamentoId($metodoPagamentoValue): ?int
    {
        try {
            $metodos = $this->configService->getMetodosPagamento();
            
            foreach ($metodos as $metodo) {
                if (
                    (isset($metodo['nome']) && strtolower($metodo['nome']) === strtolower($metodoPagamentoValue)) ||
                    (isset($metodo['codigo']) && strtolower($metodo['codigo']) === strtolower($metodoPagamentoValue)) ||
                    (isset($metodo['slug']) && strtolower($metodo['slug']) === strtolower($metodoPagamentoValue))
                ) {
                    return $metodo['id'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Erro ao buscar métodos de pagamento:', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Resolve tipo consulta ID by name or code
     */
    private function resolveTipoConsultaId($tipoConsultaValue): ?int
    {
        try {
            $tipos = $this->configService->getTiposConsulta();
            
            foreach ($tipos as $tipo) {
                if (
                    (isset($tipo['nome']) && strtolower($tipo['nome']) === strtolower($tipoConsultaValue)) ||
                    (isset($tipo['codigo']) && strtolower($tipo['codigo']) === strtolower($tipoConsultaValue))
                ) {
                    return $tipo['id'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Erro ao buscar tipos de consulta:', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Format date from various formats to Y-m-d
     */
    private function formatDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            // Se já está no formato correto, retorna
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }
            
            // Converter ISO 8601 ou outros formatos para Y-m-d
            $dateTime = new \DateTime($date);
            return $dateTime->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("⚠️ Erro ao formatar data: {$date}", ['error' => $e->getMessage()]);
            return $date; // Retorna original se falhar
        }
    }
    
    /**
     * Buscar parentes de um paciente pelo NID (usando número e ano separados)
     */
    public function getParentesByNidParts($numero, $ano)
    {
        try {
            // Construir NID no formato esperado
            $nid = sprintf('%04d/%s', (int)$numero, $ano);
            
            // Buscar paciente pelo NID
            $paciente = Paciente::where('nid', $nid)->first();
            
            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente não encontrado',
                    'nid_procurado' => $nid,
                    'data' => []
                ], 404);
            }

            // Buscar parentes do paciente
            $parentes = $paciente->parentes()->get();
            
            return response()->json([
                'success' => true,
                'data' => $parentes,
                'total' => $parentes->count(),
                'paciente_nid' => $nid,
                'message' => 'Parentes encontrados com sucesso'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parentes por NID: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Buscar parentes de um paciente pelo NID
     */
    public function getParentesByNid($nid)
    {
        try {
            // Buscar paciente pelo NID
            $paciente = Paciente::where('nid', $nid)->first();
            
            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente não encontrado',
                    'data' => []
                ], 404);
            }

            // Buscar parentes do paciente
            $parentes = $paciente->parentes()->get();
            
            return response()->json([
                'success' => true,
                'data' => $parentes,
                'total' => $parentes->count(),
                'message' => 'Parentes encontrados com sucesso'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar parentes por NID: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Enrich patient data with location names and other details
     * 
     * @param array $pacienteData
     * @return array
     */
    private function enrichPacienteData(array $pacienteData): array
    {
        // Buscar informações de localização se IDs existirem
        if (!empty($pacienteData['provincia_id'])) {
            try {
                $provincia = $this->configService->getProvincia($pacienteData['provincia_id']);
                $pacienteData['provincia_nome'] = $provincia['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['provincia_nome'] = null;
            }
        }
        
        if (!empty($pacienteData['distrito_id'])) {
            try {
                $distrito = $this->configService->getDistrito($pacienteData['distrito_id']);
                $pacienteData['distrito_nome'] = $distrito['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['distrito_nome'] = null;
            }
        }
        
        if (!empty($pacienteData['bairro_id'])) {
            try {
                $bairro = $this->configService->getBairro($pacienteData['bairro_id']);
                $pacienteData['bairro_nome'] = $bairro['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['bairro_nome'] = null;
            }
        }
        
        // Buscar outros dados de configuração se necessário
        if (!empty($pacienteData['tipo_utente_id'])) {
            try {
                $tipoUtente = $this->configService->getTipoUtente($pacienteData['tipo_utente_id']);
                $pacienteData['tipo_utente_nome'] = $tipoUtente['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['tipo_utente_nome'] = null;
            }
        }
        
        if (!empty($pacienteData['unidade_organica_id'])) {
            try {
                $unidadeOrganica = $this->configService->getUnidadeOrganica($pacienteData['unidade_organica_id']);
                $pacienteData['unidade_organica_nome'] = $unidadeOrganica['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['unidade_organica_nome'] = null;
            }
        }
        
        if (!empty($pacienteData['tipo_documento_id'])) {
            try {
                $tipoDocumento = $this->configService->getTipoDocumento($pacienteData['tipo_documento_id']);
                $pacienteData['tipo_documento_nome'] = $tipoDocumento['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['tipo_documento_nome'] = null;
            }
        }
        
        if (!empty($pacienteData['raca_id'])) {
            try {
                $raca = $this->configService->getRaca($pacienteData['raca_id']);
                $pacienteData['raca_nome'] = $raca['nome'] ?? null;
            } catch (\Exception $e) {
                $pacienteData['raca_nome'] = null;
            }
        }
        
        // Garantir que campos de texto essenciais não sejam null para o frontend
        $textFields = [
            'bilhete_identidade', 
            'documento', 
            'nome_familiar', 
            'observacoes',
            'avenida_rua_celula',
            'numero_casa', 
            'quarteirao',
            'email',
            'whatsapp',
            'celular_alternativo'
        ];
        
        foreach ($textFields as $field) {
            if (array_key_exists($field, $pacienteData) && is_null($pacienteData[$field])) {
                $pacienteData[$field] = '';
            }
        }
        
        // Adicionar campos camelCase para compatibilidade com frontend
        $camelCaseMapping = [
            'bilhete_identidade' => 'bilheteIdentidade',
            'tipo_utente_id' => 'tipoUtenteId', 
            'unidade_organica_id' => 'unidadeOrganicaId',
            'provincia_id' => 'provinciaId',
            'municipio_id' => 'municipioId',
            'distrito_urbano_id' => 'distritoUrbanoId',
            'bairro_id' => 'bairroId',
            'raca_id' => 'racaId',
            'estado_civil_id' => 'estadoCivilId',
            'escolaridade_id' => 'escolaridadeId',
            'profissao_id' => 'profissaoId',
            'data_nascimento' => 'dataNascimento',
            'nome_familiar' => 'nomeFamiliar',
            'avenida_rua_celula' => 'avenidaRuaCelula',
            'numero_casa' => 'numeroCasa',
            'celular_alternativo' => 'celularAlternativo'
        ];
        
        foreach ($camelCaseMapping as $snakeCase => $camelCase) {
            if (array_key_exists($snakeCase, $pacienteData) && !isset($pacienteData[$camelCase])) {
                $pacienteData[$camelCase] = $pacienteData[$snakeCase];
            }
        }
        
        return $pacienteData;
    }

    /**
     * Mapear string do tipo de consulta para ID
     * 
     * @param string $tipoConsultaString
     * @return int|null
     */
    private function mapTipoConsultaStringToId(string $tipoConsultaString): ?int
    {
        try {
            $tiposConsulta = $this->configService->getTiposConsulta();
            
            // Mapeamento comum de strings do frontend
            $mapping = [
                'consulta_geral' => 'consulta regular',
                'consulta_regular' => 'consulta regular',
                'consulta_especialidade' => 'consulta de especialidade',
                'consulta_urgencia' => 'consulta de urgência',
                'primeira_consulta' => 'primeira consulta',
                'emergencia' => 'emergência'
            ];
            
            // Normalizar string
            $normalizedString = strtolower(trim($tipoConsultaString));
            $searchString = $mapping[$normalizedString] ?? $normalizedString;
            
            // Buscar por nome ou código
            foreach ($tiposConsulta as $tipo) {
                $nomeNormalizado = strtolower(trim($tipo['nome'] ?? ''));
                $codigoNormalizado = strtolower(trim($tipo['codigo'] ?? ''));
                
                if ($nomeNormalizado === $searchString || 
                    $codigoNormalizado === $searchString ||
                    str_contains($nomeNormalizado, $searchString) ||
                    str_contains($searchString, $nomeNormalizado)) {
                    return (int)$tipo['id'];
                }
            }
            
            // Se não encontrou, tentar usar o ID padrão (1 para Consulta Geral)
            Log::warning('🔍 Tipo de consulta não encontrado, usando padrão:', [
                'string_recebida' => $tipoConsultaString,
                'string_normalizada' => $searchString
            ]);
            
            return 1; // ID padrão para Consulta Geral
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao mapear tipo de consulta:', [
                'string' => $tipoConsultaString,
                'error' => $e->getMessage()
            ]);
            return 1; // Fallback para ID padrão
        }
    }

    /**
     * Mapear string do tipo de utente para ID
     * 
     * @param string $tipoUtenteString
     * @return int|null
     */
    private function mapTipoUtenteStringToId(string $tipoUtenteString): ?int
    {
        try {
            $tiposUtente = $this->configService->getTiposUtentes();
            
            // Mapeamento comum de strings do frontend
            $mapping = [
                'estudante_bolseiro' => 'est-b',
                'estudante_nao_bolseiro' => 'est-nb',
                'funcionario' => 'func',
                'docente' => 'doc',
                'outro' => 'outro'
            ];
            
            // Normalizar string
            $normalizedString = strtolower(trim($tipoUtenteString));
            $searchString = $mapping[$normalizedString] ?? $normalizedString;
            
            // Buscar por código ou nome
            foreach ($tiposUtente as $tipo) {
                $codigoNormalizado = strtolower(trim($tipo['codigo'] ?? ''));
                $nomeNormalizado = strtolower(trim($tipo['nome'] ?? ''));
                
                if ($codigoNormalizado === $searchString || 
                    $nomeNormalizado === $searchString ||
                    str_contains($codigoNormalizado, $searchString) ||
                    str_contains($nomeNormalizado, $searchString)) {
                    return (int)$tipo['id'];
                }
            }
            
            // Se não encontrou, tentar usar o ID padrão (1 para o primeiro tipo)
            Log::warning('🔍 Tipo de utente não encontrado, usando padrão:', [
                'string_recebida' => $tipoUtenteString,
                'string_normalizada' => $searchString
            ]);
            
            return 1; // ID padrão
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao mapear tipo de utente:', [
                'string' => $tipoUtenteString,
                'error' => $e->getMessage()
            ]);
            return 1; // Fallback para ID padrão
        }
    }

    /**
     * Mapear código do tipo de utente para ID
     * 
     * @param string $tipoUtenteCodigo
     * @return int|null
     */
    private function mapTipoUtenteCodigoToId(string $tipoUtenteCodigo): ?int
    {
        try {
            $tiposUtente = $this->configService->getTiposUtentes();
            
            if (!$tiposUtente) {
                Log::error('❌ Tipos de utente não disponíveis');
                return 1;
            }
            
            // Buscar por código exato
            foreach ($tiposUtente as $tipo) {
                if (strtoupper(trim($tipo['codigo'] ?? '')) === strtoupper(trim($tipoUtenteCodigo))) {
                    return (int)$tipo['id'];
                }
            }
            
            Log::warning('⚠️ Código de tipo de utente não encontrado:', [
                'codigo' => $tipoUtenteCodigo,
                'tipos_disponiveis' => array_column($tiposUtente, 'codigo', 'id')
            ]);
            
            return 1; // Fallback
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao mapear código de tipo de utente:', [
                'codigo' => $tipoUtenteCodigo,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }


    /**
     * Busca dados completos do paciente por NID (formato: numero/ano)
     * Incluindo tipo_utente, email, telefone
     * GET /api/pacientes/nid/{numero}/{ano}/completo
     */
    public function getDadosCompletosByNid($numero, $ano): JsonResponse
    {
        try {
            $nid = str_pad($numero, 4, '0', STR_PAD_LEFT) . '/' . $ano;
            
            $paciente = Paciente::where('nid', $nid)->firstOrFail();
            
            // Buscar tipo de utente da tabela de configuração
            $tipoUtente = null;
            if ($paciente->tipo_utente_id) {
                $tipoUtenteConfig = DB::connection('mysql')
                    ->table('tipos_utentes')
                    ->where('id', $paciente->tipo_utente_id)
                    ->first();
                $tipoUtente = $tipoUtenteConfig->nome ?? null;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'nid' => $paciente->nid,
                    'nome' => $paciente->nome,
                    'apelido' => $paciente->apelido,
                    'genero' => $paciente->genero,
                    'data_nascimento' => $paciente->data_nascimento,
                    'tipo_utente' => $tipoUtente,
                    'email' => $paciente->email,
                    'telefone' => $paciente->telefone_principal,
                    'telefone_alternativo' => $paciente->telefone_alternativo,
                    'endereco' => $paciente->morada_completa ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados completos do paciente', [
                'nid' => $nid ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Busca hist�rico de triagens do paciente
     * GET /api/pacientes/nid/{numero}/{ano}/triagens
     */
    public function getHistoricoTriagensByNid($numero, $ano): JsonResponse
    {
        try {
            $nid = str_pad($numero, 4, '0', STR_PAD_LEFT) . '/' . $ano;
            
            $paciente = Paciente::where('nid', $nid)->firstOrFail();
            
            $triagens = DB::connection('mysql')
                ->table('triagens')
                ->where('nid', $nid)
                ->orderBy('data_triagem', 'desc')
                ->limit(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $triagens
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar hist�rico de triagens', [
                'nid' => $nid ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar hist�rico de triagens',
                'data' => []
            ], 500);
        }
    }

    /**
     * Busca hist�rico de exames do paciente
     * GET /api/pacientes/nid/{numero}/{ano}/exames
     */
    public function getHistoricoExamesByNid($numero, $ano): JsonResponse
    {
        try {
            $nid = str_pad($numero, 4, '0', STR_PAD_LEFT) . '/' . $ano;
            
            $paciente = Paciente::where('nid', $nid)->firstOrFail();
            
            $exames = DB::connection('mysql')
                ->table('exames')
                ->where('nid', $nid)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $exames
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de exames', [
                'nid' => $nid ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico de exames',
                'data' => []
            ], 500);
        }
    }

    /**
     * Busca histórico de consultas do paciente
     * GET /api/pacientes/nid/{numero}/{ano}/consultas
     */
    public function getHistoricoConsultasByNid($numero, $ano): JsonResponse
    {
        try {
            $nid = str_pad($numero, 4, '0', STR_PAD_LEFT) . '/' . $ano;
            
            $paciente = Paciente::where('nid', $nid)->firstOrFail();
            
            $consultas = DB::connection('mysql')
                ->table('consultas')
                ->where('nid', $nid)
                ->orderBy('data_hora_inicio', 'desc')
                ->limit(20)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $consultas
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico de consultas', [
                'nid' => $nid ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico de consultas',
                'data' => []
            ], 500);
        }
    }

    /**
     * Processar pagamento de transferência de especialidade
     * POST /api/pacientes/pagamento-especialidade
     */
    public function processarPagamentoEspecialidade(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|integer|exists:pacientes,id',
            'consulta_id' => 'required|integer',
            'status_pagamento' => 'required|string',
            'valor_consulta' => 'required|numeric|min:0',
            'metodo_pagamento' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Atualizar paciente no Patient Service
            $paciente = Paciente::findOrFail($request->paciente_id);
            $paciente->update([
                'status_pagamento' => 'pago',
                'data_pagamento' => now(),
                'valor_consulta' => $request->valor_consulta,
                'metodo_pagamento' => $request->metodo_pagamento,
            ]);

            // Buscar dados da consulta para criar agendamento
            $consulta = DB::connection('mysql')
                ->table('consultas')
                ->where('id', $request->consulta_id)
                ->first();

            if (!$consulta) {
                throw new \Exception('Consulta não encontrada');
            }

            // Criar agendamento no Consultation Service (8007)
            // Rota: POST http://127.0.0.1:8007/api/agenda/agendamentos
            $consultationServiceUrl = env('CONSULTATION_SERVICE_URL', 'http://127.0.0.1:8007');
            $response = \Illuminate\Support\Facades\Http::post("{$consultationServiceUrl}/api/agenda/agendamentos", [
                'consulta_id' => $request->consulta_id,
                'paciente_id' => $request->paciente_id,
                'medico_id' => $consulta->medico_id,
                'especialidade' => $consulta->especialidade,
                'status' => 'agendado',
                'status_pagamento' => 'pago',
                'valor_consulta' => $request->valor_consulta,
                'metodo_pagamento' => $request->metodo_pagamento,
                'data_agendamento' => now(),
                'tipo' => 'transferencia_especialidade'
            ]);

            if (!$response->successful()) {
                Log::warning('Erro ao criar agendamento no Consultation Service', [
                    'consulta_id' => $request->consulta_id,
                    'response' => $response->body()
                ]);
            }

            DB::commit();

            Log::info('Pagamento de especialidade processado', [
                'paciente_id' => $paciente->id,
                'consulta_id' => $request->consulta_id,
                'valor' => $request->valor_consulta
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento processado e consulta agendada com sucesso',
                'data' => $paciente->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao processar pagamento de especialidade', [
                'paciente_id' => $request->paciente_id ?? null,
                'consulta_id' => $request->consulta_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar pacientes transferidos para especialidade
     * GET /api/pacientes/transferidos-especialidade
     */
    public function listarTransferidosEspecialidade(Request $request): JsonResponse
    {
        try {
            // Buscar consultas transferidas do consultation-service
            $consultationServiceUrl = env('CONSULTATION_SERVICE_URL', 'http://127.0.0.1:8007');
            
            $params = [];
            if ($request->has('especialidade')) {
                $params['especialidade'] = $request->especialidade;
            }
            if ($request->has('medico_id')) {
                $params['medico_id'] = $request->medico_id;
            }
            if ($request->has('data_inicio')) {
                $params['data_inicio'] = $request->data_inicio;
            }
            if ($request->has('data_fim')) {
                $params['data_fim'] = $request->data_fim;
            }
            if ($request->has('per_page')) {
                $params['per_page'] = $request->per_page;
            }
            if ($request->has('page')) {
                $params['page'] = $request->page;
            }

            $response = \Illuminate\Support\Facades\Http::get(
                "{$consultationServiceUrl}/api/consultas/transferidos-especialidade",
                $params
            );

            if (!$response->successful()) {
                throw new \Exception('Erro ao buscar consultas transferidas: ' . $response->body());
            }

            $consultasData = $response->json();
            
            // Enriquecer com dados dos pacientes e especialidade anterior
            if (isset($consultasData['data']['data']) && is_array($consultasData['data']['data'])) {
                $consultasData['data']['data'] = collect($consultasData['data']['data'])->map(function ($consulta) {
                    if (isset($consulta['paciente_id'])) {
                        $paciente = Paciente::find($consulta['paciente_id']);
                        if ($paciente) {
                            $consulta['paciente'] = [
                                'id' => $paciente->id,
                                'nid' => $paciente->nid,
                                'nome' => $paciente->nome,
                                'apelido' => $paciente->apelido,
                                'genero' => $paciente->genero,
                                'data_nascimento' => $paciente->data_nascimento,
                                'telefone' => $paciente->telefone,
                                'email' => $paciente->email,
                            ];
                        }
                    }
                    
                    // Adicionar especialidade_anterior do histórico de transferência
                    if (isset($consulta['transferencia_historico']) && !empty($consulta['transferencia_historico'])) {
                        // Pegar o primeiro registro (mais recente) de transferência de especialidade
                        $transferencia = collect($consulta['transferencia_historico'])
                            ->where('tipo', 'especialidade')
                            ->first();
                        
                        if ($transferencia && isset($transferencia['especialidade_origem'])) {
                            $consulta['especialidade_anterior'] = $transferencia['especialidade_origem'];
                        }
                    }
                    
                    return $consulta;
                })->toArray();
            }

            return response()->json($consultasData);

        } catch (\Exception $e) {
            Log::error('Erro ao listar pacientes transferidos para especialidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar pacientes transferidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recebe exames solicitados pelo consultório e salva em solicitacoes_exames
     * POST /api/pacientes/nid/{numero}/{ano}/exames-solicitados
     */
    public function receberExamesSolicitados(Request $request, $numero, $ano): JsonResponse
    {
        try {
            $nid = str_pad($numero, 4, '0', STR_PAD_LEFT) . '/' . $ano;

            $paciente = Paciente::where('nid', $nid)->first();
            if (!$paciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente não encontrado com NID: ' . $nid
                ], 404);
            }

            $exames     = $request->input('exames', []);
            $consultaId = $request->input('consulta_id');
            $medicoId   = $request->input('medico_id');
            $medicoNome = $request->input('medico_nome');

            if (empty($exames)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum exame informado'
                ], 422);
            }

            // Se já existe solicitação para esta consulta, actualiza em vez de duplicar
            if ($consultaId) {
                $existente = \App\Models\SolicitacaoExame::where('consulta_id', $consultaId)
                    ->whereNotIn('status', ['cancelada', 'concluida'])
                    ->first();

                if ($existente) {
                    $existente->update([
                        'exames_solicitados' => $exames,
                        'exames'             => $exames,
                        'data_solicitacao'   => $request->input('data_solicitacao', now()),
                        'observacoes'        => $request->input('observacoes', $existente->observacoes),
                    ]);

                    Log::info('Solicitação de exames actualizada', [
                        'consulta_id'    => $consultaId,
                        'nid'            => $nid,
                        'solicitacao_id' => $existente->id
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Solicitação de exames actualizada',
                        'data'    => $existente
                    ]);
                }
            }

            $solicitacao = \App\Models\SolicitacaoExame::create([
                'consulta_id'        => $consultaId,
                'paciente_id'        => $paciente->id,
                'nid'                => $nid,
                'paciente_nid'       => $nid,
                'solicitante_id'     => $medicoId,
                'medico_nome'        => $medicoNome,
                'exames_solicitados' => $exames,
                'exames'             => $exames,
                'data_solicitacao'   => $request->input('data_solicitacao', now()),
                'status'             => 'pendente',
                'observacoes'        => $request->input('observacoes'),
            ]);

            Log::info('Solicitação de exames criada a partir do consultório', [
                'consulta_id'    => $consultaId,
                'nid'            => $nid,
                'solicitacao_id' => $solicitacao->id,
                'qtd_exames'     => count($exames)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exames solicitados registados com sucesso',
                'data'    => $solicitacao
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao receber exames solicitados do consultório', [
                'error' => $e->getMessage(),
                'nid'   => $nid ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao registar exames solicitados',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
