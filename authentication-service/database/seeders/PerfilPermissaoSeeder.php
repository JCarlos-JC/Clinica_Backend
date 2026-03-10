<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PerfilPermissaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Associando permissões ao perfil Administrador
        $perfilAdmin = Perfil::where('nome', 'Administrador')->first();
        if ($perfilAdmin) {
            // Administrador tem todas as permissões
            $todasPermissoes = Permissao::all();
            $perfilAdmin->permissoes()->attach($todasPermissoes->pluck('id')->toArray());
        }

        // Associando permissões ao perfil Médico
        $perfilMedico = Perfil::where('nome', 'Médico')->first();
        if ($perfilMedico) {
            $permissoesMedico = Permissao::whereIn('nome', [
                'pacientes.listar', 'pacientes.visualizar',
                'consultas.listar', 'consultas.visualizar', 'consultas.criar', 'consultas.editar',
                'exames.listar', 'exames.visualizar', 'exames.criar',
                'prescricoes.listar', 'prescricoes.visualizar', 'prescricoes.criar', 'prescricoes.editar',
                'triagem.visualizar',
            ])->get();
            
            $perfilMedico->permissoes()->attach($permissoesMedico->pluck('id')->toArray());
        }

        // Associando permissões ao perfil Enfermeiro
        $perfilEnfermeiro = Perfil::where('nome', 'Enfermeiro')->first();
        if ($perfilEnfermeiro) {
            $permissoesEnfermeiro = Permissao::whereIn('nome', [
                'pacientes.listar', 'pacientes.visualizar',
                'triagem.listar', 'triagem.visualizar', 'triagem.criar', 'triagem.editar',
                'consultas.listar', 'consultas.visualizar',
                'exames.listar', 'exames.visualizar',
            ])->get();
            
            $perfilEnfermeiro->permissoes()->attach($permissoesEnfermeiro->pluck('id')->toArray());
        }

        // Associando permissões ao perfil Recepcionista
        $perfilRecepcionista = Perfil::where('nome', 'Recepcionista')->first();
        if ($perfilRecepcionista) {
            $permissoesRecepcionista = Permissao::whereIn('nome', [
                'pacientes.listar', 'pacientes.visualizar', 'pacientes.criar', 'pacientes.editar',
                'consultas.listar', 'consultas.visualizar', 'consultas.criar', 'consultas.editar',
            ])->get();
            
            $perfilRecepcionista->permissoes()->attach($permissoesRecepcionista->pluck('id')->toArray());
        }

        // Associando permissões ao perfil Paciente
        $perfilPaciente = Perfil::where('nome', 'Paciente')->first();
        if ($perfilPaciente) {
            $permissoesPaciente = Permissao::whereIn('nome', [
                'consultas.listar', 'consultas.visualizar',
                'exames.listar', 'exames.visualizar',
                'prescricoes.listar', 'prescricoes.visualizar',
            ])->get();
            
            $perfilPaciente->permissoes()->attach($permissoesPaciente->pluck('id')->toArray());
        }
    }
}