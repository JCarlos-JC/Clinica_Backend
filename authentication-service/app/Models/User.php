<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'cargo',
        'ativo',
        'ultimo_login',
        'criado_por',
        'modificado_por',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ativo' => 'boolean',
        'ultimo_login' => 'datetime',
    ];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->senha;
    }

    /**
     * The roles that belong to the user.
     */
    public function perfis()
    {
        return $this->belongsToMany(Perfil::class, 'usuario_perfil', 'usuario_id', 'perfil_id')
                    ->where('ativo', true);
    }

    /**
     * Check if user has a specific role.
     *
     * @param string|array $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->perfis->contains('codigo', $role);
        }
        
        if (is_array($role)) {
            foreach ($role as $r) {
                if ($this->perfis->contains('codigo', $r)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if user has any permission through any of their roles.
     *
     * @param string|array $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        // Get all permissions from all roles
        $allPermissions = $this->perfis->flatMap(function ($role) {
            return $role->permissoes;
        })->pluck('codigo')->unique();

        if (is_string($permission)) {
            return $allPermissions->contains($permission);
        }
        
        if (is_array($permission)) {
            foreach ($permission as $p) {
                if ($allPermissions->contains($p)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // Add roles and permissions to the token claims
        $roles = $this->perfis->pluck('codigo')->toArray();
        $permissions = $this->perfis->flatMap(function ($role) {
            return $role->permissoes;
        })->pluck('codigo')->unique()->toArray();

        return [
            'nome' => $this->nome,
            'email' => $this->email,
            'cargo' => $this->cargo,
            'roles' => $roles,
            'permissions' => $permissions
        ];
    }

    /**
     * Get authentication logs for this user.
     */
    public function authLogs()
    {
        return $this->hasMany(LogAutenticacao::class, 'usuario_id');
    }
}