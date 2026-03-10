<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Obito;
use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ObitoController extends Controller
{
    /**
     * Listar todos os óbitos
     */
    public function index(Request $request)
    {
        $query = Obito::with('consulta');

        // Filtros
        if ($request->has('nid')) {
            $query->porPaciente($request->nid);
        }

        if ($request->has('tipo_obito')) {
            $query->where('tipo_obito', $request->tipo_obito);
        }

        if ($request->has('natural')) {
            $query->natural();
        }

        if ($request->has('violento')) {
            $query->violento();
        }

        if ($request->has('aguardando_declaracao')) {
            $query->aguardandoDeclaracao();
        }

        if ($request->has('aguardando_liberacao')) {
            $query->aguardandoLiberacao();
        }

        if ($request->has('com_necropsia')) {
            $query->comNecropsia();
        }

        $obitos = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($obitos);
    }

    /**
     * Exibir um óbito específico
     */
    public function show($id)
    {
        $obito = Obito::with('consulta')->findOrFail($id);
        return response()->json($obito);
    }

    /**
     * Criar novo óbito
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consulta_id' => 'required|exists:consultas,id',
            'nid' => 'required|string',
            'data_hora_obito' => 'required|date',
            'local_obito' => 'required|string',
            'tipo_obito' => 'required|string',
            'medico_atestante_id' => 'required|integer',
            'medico_atestante_nome' => 'required|string',
            'medico_atestante_crm' => 'required|string',
            'causa_imediata' => 'required|string',
            'causa_basica' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar causas de morte
        try {
            Obito::validarCausaMorte(
                $request->causa_imediata,
                $request->causa_basica
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na validação das causas de morte',
                'error' => $e->getMessage()
            ], 422);
        }

        $obito = Obito::create($request->all());

        return response()->json([
            'message' => 'Óbito registrado com sucesso',
            'data' => $obito
        ], 201);
    }

    /**
     * Atualizar óbito
     */
    public function update(Request $request, $id)
    {
        $obito = Obito::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tipo_obito' => 'sometimes|string',
            'causa_imediata' => 'sometimes|string',
            'causa_basica' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $obito->update($request->all());

        return response()->json([
            'message' => 'Óbito atualizado com sucesso',
            'data' => $obito
        ]);
    }

    /**
     * Deletar óbito
     */
    public function destroy($id)
    {
        $obito = Obito::findOrFail($id);
        
        // Validar se pode deletar (questões legais)
        if ($obito->declaracao_emitida) {
            return response()->json([
                'message' => 'Não é possível deletar óbito com declaração emitida'
            ], 403);
        }

        $obito->delete();

        return response()->json([
            'message' => 'Óbito removido com sucesso'
        ]);
    }

    /**
     * Registrar declaração de óbito
     */
    public function registrarDeclaracao(Request $request, $id)
    {
        $obito = Obito::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'numero_declaracao_obito' => 'required|string',
            'emitida_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $obito->registrarDeclaracao(
                $request->numero_declaracao_obito,
                $request->emitida_por
            );

            return response()->json([
                'message' => 'Declaração de óbito registrada com sucesso',
                'data' => $obito->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar declaração',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Liberar corpo
     */
    public function liberarCorpo(Request $request, $id)
    {
        $obito = Obito::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'liberado_por' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $obito->validarLiberacaoCorpo();
            $obito->liberarCorpo($request->liberado_por);

            return response()->json([
                'message' => 'Corpo liberado com sucesso',
                'data' => $obito->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao liberar corpo',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Registrar sepultamento
     */
    public function registrarSepultamento(Request $request, $id)
    {
        $obito = Obito::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tipo' => 'required|string|in:sepultamento,cremacao',
            'local' => 'required|string',
            'endereco' => 'required|string',
            'data' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $obito->registrarSepultamento($request->all());

            return response()->json([
                'message' => 'Sepultamento registrado com sucesso',
                'data' => $obito->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar sepultamento',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Vincular funerária
     */
    public function vincularFuneraria(Request $request, $id)
    {
        $obito = Obito::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'funeraria' => 'required|string',
            'contato' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $obito->vincularFuneraria(
                $request->funeraria,
                $request->contato,
                $request->protocolo
            );

            return response()->json([
                'message' => 'Funerária vinculada com sucesso',
                'data' => $obito->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao vincular funerária',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Registrar cartório
     */
    public function registrarCartorio(Request $request, $id)
    {
        $obito = Obito::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string',
            'numero_registro' => 'sometimes|string',
            'livro' => 'sometimes|string',
            'folha' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $obito->registrarCartorio($request->all());

            return response()->json([
                'message' => 'Cartório registrado com sucesso',
                'data' => $obito->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar cartório',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Listar óbito de uma consulta
     */
    public function porConsulta($consultaId)
    {
        $consulta = Consulta::findOrFail($consultaId);
        $obito = $consulta->obito;

        if (!$obito) {
            return response()->json([
                'message' => 'Nenhum óbito encontrado para esta consulta'
            ], 404);
        }

        return response()->json($obito);
    }

    /**
     * Listar óbitos naturais
     */
    public function naturais()
    {
        $obitos = Obito::natural()
            ->with('consulta')
            ->orderBy('data_hora_obito', 'desc')
            ->get();

        return response()->json($obitos);
    }

    /**
     * Listar óbitos violentos
     */
    public function violentos()
    {
        $obitos = Obito::violento()
            ->with('consulta')
            ->orderBy('data_hora_obito', 'desc')
            ->get();

        return response()->json($obitos);
    }

    /**
     * Listar óbitos aguardando declaração
     */
    public function aguardandoDeclaracao()
    {
        $obitos = Obito::aguardandoDeclaracao()
            ->with('consulta')
            ->orderBy('data_hora_obito', 'asc')
            ->get();

        return response()->json($obitos);
    }

    /**
     * Listar óbitos aguardando liberação do corpo
     */
    public function aguardandoLiberacao()
    {
        $obitos = Obito::aguardandoLiberacao()
            ->with('consulta')
            ->orderBy('data_emissao_do', 'asc')
            ->get();

        return response()->json($obitos);
    }

    /**
     * Listar óbitos com necropsia
     */
    public function comNecropsia()
    {
        $obitos = Obito::comNecropsia()
            ->with('consulta')
            ->orderBy('data_hora_obito', 'desc')
            ->get();

        return response()->json($obitos);
    }
}
