<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Rota login nomeada para evitar o erro "Route [login] not defined"
Route::get('/login', function (Request $request) {
    if ($request->expectsJson()) {
        return response()->json(['error' => 'Não autenticado.'], 401);
    }
    return view('auth.login');
})->name('login');
