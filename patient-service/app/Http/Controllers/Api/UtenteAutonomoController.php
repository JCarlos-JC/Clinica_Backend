<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UtenteAutonomo;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UtenteAutonomoController extends Controller
{
    protected ConfigurationService $configService;

    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Display a listing of utentes autonomos.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = UtenteAutonomo::query();

            // Filtros
            if ($request->has('status')) {
                $query->status($request->status);
            }

            if ($request->has('tipo_documento_id')) {
                $query->where('tipo_documento_id', $request->tipo_documento_id);
            }

            if ($request->has('hospital_proveniencia')) {
                $query->where('hospital_proveniencia', 'like', "%{$request->hospital_proveniencia}%");
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginação
            $perPage = $request->get('per_page', 15);
            
            if ($request->has('paginate') && $request->paginate === 'false') {
                $utentes = $query->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $utentes,
                    'total' => $utentes->count(),
                ]);
            }

            $utentes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $utentes->items(),
                'meta' => [
                    'current_page' => $utentes->currentPage(),
                    'last_page' => $utentes->lastPage(),
                    'per_page' => $utentes->perPage(),
                    'total' => $utentes->total(),
                    'from' => $utentes->firstItem(),
                    'to' => $utentes->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar utentes autônomos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created utente autonomo.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Mapear dados do frontend para formato backend
            $data = $this->mapFrontendDataToBackend($request->all());
            Log::info('📥 Dados mapeados para criar utente autônomo:', $data);
            
            $validator = Validator::make($data, [
                // Informações Pessoais
                'nome' => 'required|string|max:255',
                'apelido' => 'required|string|max:255',
                'data_nascimento' => 'required|date|before:today',
                'genero' => 'required|in:Masculino,Feminino,Outro',
                'tipo_documento_id' => 'required|integer',
                'bilhete_identidade' => 'required|string|max:50',
                
                // Informações de Contato
                'celular' => 'required|string|max:20',
                'celular_alternativo' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                
                // Hospital de Proveniência
                'hospital_proveniencia' => 'required|string|max:255',
                
                // Solicitações de Exames
                'exames_solicitados' => 'nullable|json',
                'data_solicitacao' => 'nullable|date',
                'status' => 'nullable|in:pendente,aceito,pago,pago_laboratorio,processando,concluido',
                
                // Pagamento
                'tipos_exame_id' => 'nullable|integer',
                'metodo_pagamento_id' => 'nullable|integer',
                'data_pagamento' => 'nullable|date',
                
                // Resultados de Exames
                'resultados_exames' => 'nullable|json',
                'data_resultados' => 'nullable|date',
                'data_exames' => 'nullable|date',
                
                // Histórico
                'historico_exames' => 'nullable|json',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validar tipo_documento_id no configuration-service (se fornecido)
            if (isset($data['tipo_documento_id']) && $data['tipo_documento_id']) {
                if (!$this->configService->validateTipoDocumento($data['tipo_documento_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro de validação',
                        'errors' => ['tipo_documento_id' => 'Tipo de documento inválido'],
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Usar apenas os campos validados dos dados mapeados
            $validatedData = $validator->validated();
            Log::info('✅ Dados validados para criar utente:', $validatedData);
            
            $utente = UtenteAutonomo::create($validatedData);
            Log::info('💾 Utente autônomo criado:', $utente->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utente autônomo criado com sucesso',
                'data' => $utente,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar utente autônomo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified utente autonomo.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $utente = UtenteAutonomo::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $utente,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utente autônomo não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar utente autônomo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified utente autonomo.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $utente = UtenteAutonomo::findOrFail($id);
            
            // Mapear dados do frontend para formato backend
            $data = $this->mapFrontendDataToBackend($request->all());
            
            // Remover campos que não devem ser atualizados
            unset($data['authenticated_user'], $data['nid']);
            
            $validator = Validator::make($data, [
                // Informações Pessoais
                'nome' => 'sometimes|required|string|max:255',
                'apelido' => 'sometimes|required|string|max:255',
                'data_nascimento' => 'sometimes|required|date|before:today',
                'genero' => 'sometimes|required|in:Masculino,Feminino,Outro',
                'tipo_documento_id' => 'sometimes|required|integer',
                'bilhete_identidade' => 'sometimes|required|string|max:50',
                
                // Informações de Contato
                'celular' => 'sometimes|required|string|max:20',
                'celular_alternativo' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                
                // Hospital de Proveniência
                'hospital_proveniencia' => 'sometimes|required|string|max:255',
                
                // Solicitações de Exames
                'exames_solicitados' => 'nullable|json',
                'data_solicitacao' => 'nullable|date',
                'status' => 'nullable|in:pendente,aceito,pago,pago_laboratorio,processando,concluido',
                
                // Pagamento
                'tipos_exame_id' => 'nullable|integer',
                'metodo_pagamento_id' => 'nullable|integer',
                'data_pagamento' => 'nullable|date',
                
                // Resultados de Exames
                'resultados_exames' => 'nullable|json',
                'data_resultados' => 'nullable|date',
                'data_exames' => 'nullable|date',
                
                // Histórico
                'historico_exames' => 'nullable|json',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validar tipo_documento_id (se fornecido)
            if (isset($data['tipo_documento_id']) && $data['tipo_documento_id']) {
                if (!$this->configService->validateTipoDocumento($data['tipo_documento_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro de validação',
                        'errors' => ['tipo_documento_id' => 'Tipo de documento inválido'],
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Usar apenas os campos validados dos dados mapeados
            $validatedData = $validator->validated();
            $utente->update($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utente autônomo atualizado com sucesso',
                'data' => $utente->fresh(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utente autônomo não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar utente autônomo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified utente autonomo (soft delete).
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $utente = UtenteAutonomo::findOrFail($id);

            DB::beginTransaction();
            
            $utente->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utente autônomo excluído com sucesso',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utente autônomo não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir utente autônomo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a soft deleted utente autonomo.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $utente = UtenteAutonomo::onlyTrashed()->findOrFail($id);

            DB::beginTransaction();

            $utente->restore();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utente autônomo restaurado com sucesso',
                'data' => $utente,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utente autônomo não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar utente autônomo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change utente autonomo status.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pendente,aceito,pago,pago_laboratorio,processando,concluido',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $utente = UtenteAutonomo::findOrFail($id);

            DB::beginTransaction();

            $utente->status = $request->status;
            $utente->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso',
                'data' => $utente,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utente autônomo não encontrado',
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
     * Get statistics.
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => UtenteAutonomo::count(),
                'por_status' => [
                    'pendente' => UtenteAutonomo::status('pendente')->count(),
                    'aceito' => UtenteAutonomo::status('aceito')->count(),
                    'pago' => UtenteAutonomo::status('pago')->count(),
                    'pago_laboratorio' => UtenteAutonomo::status('pago_laboratorio')->count(),
                    'processando' => UtenteAutonomo::status('processando')->count(),
                    'concluido' => UtenteAutonomo::status('concluido')->count(),
                ],
                'com_pagamento' => UtenteAutonomo::whereNotNull('data_pagamento')->count(),
                'com_resultados' => UtenteAutonomo::whereNotNull('data_resultados')->count(),
                'criados_hoje' => UtenteAutonomo::whereDate('created_at', today())->count(),
                'criados_esta_semana' => UtenteAutonomo::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'criados_este_mes' => UtenteAutonomo::whereMonth('created_at', now()->month)
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
     * Get next available NID.
     * 
     * @return JsonResponse
     */
    public function nextNID(): JsonResponse
    {
        try {
            $nextNID = UtenteAutonomo::proximoNID();

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
     * Map frontend data (camelCase) to backend format (snake_case).
     * 
     * @param array $data
     * @return array
     */
    private function mapFrontendDataToBackend(array $data): array
    {
        $mapped = [];
        
        Log::info('🔄 Iniciando mapeamento de dados', ['dados_recebidos' => $data]);
        
        // Mapeamento de campos do frontend para backend
        $fieldMapping = [
            'hospitalProveniencia' => 'hospital_proveniencia',
            'dataNascimento' => 'data_nascimento',
            'tipoDocumento' => 'tipo_documento_id',
            'bilheteIdentidade' => 'bilhete_identidade',
            'celularalternativo' => 'celular_alternativo',
        ];
        
        foreach ($data as $key => $value) {
            // Usar mapeamento se existir, senão manter a chave original
            $mappedKey = $fieldMapping[$key] ?? $key;
            
            Log::debug("Mapeando campo: {$key} -> {$mappedKey}", ['valor' => $value]);
            
            // Converter strings vazias e "0" para null em campos integer
            if (in_array($mappedKey, ['tipo_documento_id']) && (empty($value) || $value === "0")) {
                $mapped[$mappedKey] = null;
            } else {
                $mapped[$mappedKey] = $value;
            }
        }
        
        // Formatar data_nascimento se presente
        if (isset($mapped['data_nascimento']) && !empty($mapped['data_nascimento'])) {
            $mapped['data_nascimento'] = $this->formatDate($mapped['data_nascimento']);
        }
        
        Log::info('✅ Dados mapeados finais', ['dados_mapeados' => $mapped]);
        
        return $mapped;
    }
    
    /**
     * Format date from various formats to Y-m-d.
     * 
     * @param string $date
     * @return string|null
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
            return $date; // Retorna original se falhar
        }
    }
}
