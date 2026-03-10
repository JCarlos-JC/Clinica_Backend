<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parente;
use App\Models\Paciente;
use App\Models\HistoricoPaciente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ParenteController extends Controller
{
    /**
     * Display a listing of parentes.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Parente::query();

            // Filtrar por paciente
            if ($request->has('paciente_id')) {
                $query->where('paciente_id', $request->paciente_id);
            }

            // Filtrar por grau de parentesco
            if ($request->has('grau_parentesco_id')) {
                $query->where('grau_parentesco_id', $request->grau_parentesco_id);
            }

            // Buscar por nome
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('apelido', 'like', "%{$search}%")
                      ->orWhere('celular', 'like', "%{$search}%");
                });
            }

            // Relacionamentos
            if ($request->has('with')) {
                $relations = explode(',', $request->with);
                $query->with($relations);
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'nome');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginação
            $perPage = $request->get('per_page', 15);
            
            if ($request->has('paginate') && $request->paginate === 'false') {
                $parentes = $query->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $parentes,
                    'total' => $parentes->count(),
                ]);
            }

            $parentes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $parentes->items(),
                'meta' => [
                    'current_page' => $parentes->currentPage(),
                    'last_page' => $parentes->lastPage(),
                    'per_page' => $parentes->perPage(),
                    'total' => $parentes->total(),
                    'from' => $parentes->firstItem(),
                    'to' => $parentes->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar parentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display parentes of a specific patient.
     * 
     * @param int $pacienteId
     * @return JsonResponse
     */
    public function byPaciente(int $pacienteId): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($pacienteId);
            
            $parentes = Parente::where('paciente_id', $pacienteId)
                ->orderBy('nome')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $parentes,
                'paciente' => [
                    'id' => $paciente->id,
                    'nid' => $paciente->nid,
                    'nome_completo' => $paciente->nome_completo,
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
                'message' => 'Erro ao buscar parentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created parente.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validação - aceita APENAS paciente_nid (identificador único)
            $validator = Validator::make($request->all(), [
                'paciente_nid' => 'required|string|exists:pacientes,nid',
                'nome' => 'required|string|max:255',
                'celular' => 'required|string|max:20',
                'celular_alternativo' => 'nullable|string|max:20',
                'grau_parentesco_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $data = $validator->validated();
            
            // Buscar o paciente pelo NID para registrar no histórico
            $paciente = Paciente::where('nid', $data['paciente_nid'])->firstOrFail();

            $parente = Parente::create($data);

            // // Registrar no histórico do paciente
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $paciente->id,
            //     tipoOperacao: 'atualizacao',
            //     dadosNovos: $parente->toArray(),
            //     observacao: "Parente adicionado: {$parente->nome}"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parente criado com sucesso',
                'data' => $parente->load('paciente'),
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado com o NID informado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar parente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified parente.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $parente = Parente::with('paciente')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $parente,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar parente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified parente.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $parente = Parente::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => 'sometimes|required|string|max:255',
                'apelido' => 'sometimes|required|string|max:255',
                'celular' => 'sometimes|required|string|max:20',
                'celular_alternativo' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'grau_parentesco_id' => 'sometimes|required|integer',
                'observacoes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $dadosAnteriores = $parente->toArray();
            
            $parente->update($validator->validated());

            // // Registrar no histórico do paciente
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $parente->paciente_id,
            //     tipoOperacao: 'atualizacao',
            //     dadosAnteriores: $dadosAnteriores,
            //     dadosNovos: $parente->toArray(),
            //     observacao: "Parente atualizado: {$parente->nome} {$parente->apelido}"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parente atualizado com sucesso',
                'data' => $parente->fresh('paciente'),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar parente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified parente.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $parente = Parente::findOrFail($id);

            DB::beginTransaction();

            $dadosAnteriores = $parente->toArray();
            $pacienteId = $parente->paciente_id;
            
            $parente->delete();

            // // Registrar no histórico do paciente
            // HistoricoPaciente::logOperacao(
            //     pacienteId: $pacienteId,
            //     tipoOperacao: 'atualizacao',
            //     dadosAnteriores: $dadosAnteriores,
            //     observacao: "Parente removido: {$dadosAnteriores['nome']} {$dadosAnteriores['apelido']}"
            // );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parente excluído com sucesso',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parente não encontrado',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir parente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
