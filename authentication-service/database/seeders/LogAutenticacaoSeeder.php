<?php

namespace Database\Seeders;

use App\Models\LogAutenticacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class LogAutenticacaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@clinica.com')->first();
        
        if ($admin) {
            // Criar alguns logs de autenticação para o admin
            LogAutenticacao::create([
                'usuario_id' => $admin->id,
                'email' => $admin->email,
                'ip' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                'tipo' => 'login',
                'mensagem' => 'Login bem-sucedido',
                'created_at' => Date::now()->subDays(7),
                'updated_at' => Date::now()->subDays(7),
            ]);

            LogAutenticacao::create([
                'usuario_id' => $admin->id,
                'email' => $admin->email,
                'ip' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                'tipo' => 'logout',
                'mensagem' => 'Logout realizado',
                'created_at' => Date::now()->subDays(7)->addHours(2),
                'updated_at' => Date::now()->subDays(7)->addHours(2),
            ]);

            LogAutenticacao::create([
                'usuario_id' => $admin->id,
                'email' => $admin->email,
                'ip' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                'tipo' => 'login',
                'mensagem' => 'Login bem-sucedido',
                'created_at' => Date::now()->subDays(3),
                'updated_at' => Date::now()->subDays(3),
            ]);
        }

        // Criar alguns logs de falha de autenticação
        LogAutenticacao::create([
            'email' => 'usuario.inexistente@email.com',
            'ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'tipo' => 'failed_attempt',
            'mensagem' => 'Usuário não encontrado',
            'created_at' => Date::now()->subDays(1),
            'updated_at' => Date::now()->subDays(1),
        ]);

        LogAutenticacao::create([
            'email' => 'admin@clinica.com',
            'ip' => '192.168.1.101',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36',
            'tipo' => 'failed_attempt',
            'mensagem' => 'Senha incorreta',
            'created_at' => Date::now()->subHours(12),
            'updated_at' => Date::now()->subHours(12),
        ]);
    }
}