<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Adicionar rota de login para evitar o erro "Route [login] not defined"
Route::get('/login', function () {
    return response()->json(['message' => 'Please login to access this resource'], 401);
})->name('login');