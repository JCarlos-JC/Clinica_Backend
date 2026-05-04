<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserPerfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Associando o usuário admin ao perfil Administrador
        $admin = User::where('email', 'admin@clinica.com')->first();
        $perfilAdmin = Perfil::where('nome', 'Administrador')->first();
        if ($admin && $perfilAdmin) {
            $admin->perfis()->attach($perfilAdmin->id);
        }

        // Associando o usuário médico ao perfil Médico
        $medico = User::where('email', 'joao.silva@clinica.com')->first();
        $perfilMedico = Perfil::where('nome', 'Médico')->first();
        if ($medico && $perfilMedico) {
            $medico->perfis()->attach($perfilMedico->id);
        }

        // Associando o usuário enfermeiro ao perfil Enfermeiro
        $enfermeiro = User::where('email', 'maria.santos@clinica.com')->first();
        $perfilEnfermeiro = Perfil::where('nome', 'Enfermeiro')->first();
        if ($enfermeiro && $perfilEnfermeiro) {
            $enfermeiro->perfis()->attach($perfilEnfermeiro->id);
        }

        // Associando o usuário recepcionista ao perfil Recepcionista
        $recepcionista = User::where('email', 'ana.pereira@clinica.com')->first();
        $perfilRecepcionista = Perfil::where('nome', 'Recepcionista')->first();
        if ($recepcionista && $perfilRecepcionista) {
            $recepcionista->perfis()->attach($perfilRecepcionista->id);
        }

        // Associando o usuário paciente ao perfil Paciente
        $paciente = User::where('email', 'pedro@email.com')->first();
        $perfilPaciente = Perfil::where('nome', 'Médico')->first();
        if ($paciente && $perfilPaciente) {
            $paciente->perfis()->attach($perfilPaciente->id);
        }
    }
}