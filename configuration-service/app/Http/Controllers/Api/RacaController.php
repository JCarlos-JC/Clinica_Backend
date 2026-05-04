<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Raca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class RacaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $racas = Raca::all();
        return response()->json($racas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100|unique:racas',
            'codigo' => 'required|string|max:10|unique:racas',
        ]);

        $raca = Raca::create($request->all());
        return response()->json($raca, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $raca = Raca::findOrFail($id);
        return response()->json($raca);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $raca = Raca::findOrFail($id);
        
        $request->validate([
            'nome' => 'sometimes|required|string|max:100|unique:racas,nome,'.$id,
            'codigo' => 'sometimes|required|string|max:10|unique:racas,codigo,'.$id,
        ]);

        $raca->update($request->all());
        return response()->json($raca);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $raca = Raca::findOrFail($id);
        $raca->delete();
        return response()->json(null, 204);
    }
}




// class RacaController extends Controller
// {
//     /**
//      * Display a listing of the resource.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function index()
//     {
//         $racas = Raca::when(request('ativo') !== null, function ($query) {
//             return $query->where('ativo', request('ativo'));
//         })->get();
        
//         return response()->json([
//             'status' => 'success',
//             'data' => $racas
//         ]);
//     }
    
//     /**
//      * Get active races for public access.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function publicIndex()
//     {
//         $racas = Raca::where('ativo', true)
//             ->select('id', 'nome')
//             ->get();
        
//         return response()->json([
//             'status' => 'success',
//             'data' => $racas
//         ]);
//     }

//     /**
//      * Store a newly created resource in storage.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @return \Illuminate\Http\Response
//      */
//     public function store(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'nome' => 'required|string|max:100|unique:racas',
//             'codigo' => 'nullable|string|max:20|unique:racas',
//             'descricao' => 'nullable|string',
//             'ativo' => 'nullable|boolean',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Dados de entrada inválidos',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $raca = Raca::create($request->all());

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Raça criada com sucesso',
//             'data' => $raca
//         ], 201);
//     }

//     /**
//      * Display the specified resource.
//      *
//      * @param  int  $id
//      * @return \Illuminate\Http\Response
//      */
//     public function show($id)
//     {
//         $raca = Raca::find($id);

//         if (!$raca) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Raça não encontrada'
//             ], 404);
//         }

//         return response()->json([
//             'status' => 'success',
//             'data' => $raca
//         ]);
//     }

//     /**
//      * Update the specified resource in storage.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  int  $id
//      * @return \Illuminate\Http\Response
//      */
//     public function update(Request $request, $id)
//     {
//         $raca = Raca::find($id);

//         if (!$raca) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Raça não encontrada'
//             ], 404);
//         }

//         $validator = Validator::make($request->all(), [
//             'nome' => 'sometimes|required|string|max:100|unique:racas,nome,'.$id,
//             'codigo' => 'nullable|string|max:20|unique:racas,codigo,'.$id,
//             'descricao' => 'nullable|string',
//             'ativo' => 'nullable|boolean',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Dados de entrada inválidos',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $raca->update($request->all());

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Raça atualizada com sucesso',
//             'data' => $raca
//         ]);
//     }

//     /**
//      * Remove the specified resource from storage.
//      *
//      * @param  int  $id
//      * @return \Illuminate\Http\Response
//      */
//     public function destroy($id)
//     {
//         $raca = Raca::find($id);

//         if (!$raca) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Raça não encontrada'
//             ], 404);
//         }

//         // Alternar para inativo em vez de excluir fisicamente
//         $raca->update(['ativo' => false]);

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Raça desativada com sucesso'
//         ]);
//     }
// }