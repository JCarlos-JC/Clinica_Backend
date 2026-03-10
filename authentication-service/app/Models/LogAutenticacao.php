<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAutenticacao extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logs_autenticacao';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'email',
        'ip',
        'user_agent',
        'tipo', // login, logout, failed_attempt, refresh_token
        'mensagem',
    ];

    /**
     * Get the user that owns the log.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Create a login success log
     *
     * @param User $user
     * @return LogAutenticacao
     */
    public static function loginSuccess(User $user)
    {
        return self::create([
            'usuario_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tipo' => 'login',
            'mensagem' => 'Login bem-sucedido'
        ]);
    }

    /**
     * Create a login failed log
     *
     * @param string $email
     * @param string $reason
     * @return LogAutenticacao
     */
    public static function loginFailed($email, $reason = 'Credenciais inválidas')
    {
        return self::create([
            'email' => $email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tipo' => 'failed_attempt',
            'mensagem' => $reason
        ]);
    }

    /**
     * Create a logout log
     *
     * @param User $user
     * @return LogAutenticacao
     */
    public static function logout(User $user)
    {
        return self::create([
            'usuario_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tipo' => 'logout',
            'mensagem' => 'Logout realizado'
        ]);
    }
}