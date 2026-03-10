<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Para API, não redirecionamos para a rota 'login', apenas retornamos um erro JSON
        // Isso evita o erro "Route [login] not defined."
        return null;
    }
}