<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criação do usuário administrador
        User::create([
            'nome' => 'Administrador',
            'email' => 'admin@clinica.com',
            'senha' => Hash::make('admin12345'),
            'cargo' => 'Administrador do Sistema',
            'ativo' => true,
        ]);

        // Criação de usuário médico
        User::create([
            'nome' => 'Dr. João Silva',
            'email' => 'joao.silva@clinica.com',
            'senha' => Hash::make('senha12345'),
            'cargo' => 'Médico Clínico Geral',
            'ativo' => true,
        ]);

        // Criação de usuário enfermeiro
        User::create([
            'nome' => 'Enf. Maria Santos',
            'email' => 'maria.santos@clinica.com',
            'senha' => Hash::make('senha12345'),
            'cargo' => 'Enfermeira Chefe',
            'ativo' => true,
        ]);

        // Criação de usuário recepcionista
        User::create([
            'nome' => 'Ana Pereira',
            'email' => 'ana.pereira@clinica.com',
            'senha' => Hash::make('senha12345'),
            'cargo' => 'Recepcionista',
            'ativo' => true,
        ]);

        // Criação de usuário paciente
        User::create([
            'nome' => 'Pedro Paciente',
            'email' => 'pedro.paciente@email.com',
            'senha' => Hash::make('senha12345'),
            'cargo' => null,
            'ativo' => true,
        ]);
    }
}