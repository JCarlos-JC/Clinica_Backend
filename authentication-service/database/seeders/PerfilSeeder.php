<?php

namespace Database\Seeders;

use App\Models\Perfil;
use Illuminate\Database\Seeder;

class PerfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perfis = [
            [
                'nome' => 'Administrador',
                'codigo' => 'admin',
                'descricao' => 'Administrador do sistema com acesso total',
            ],
            [
                'nome' => 'Médico',
                'codigo' => 'medico',
                'descricao' => 'Médico com acesso às consultas e prontuários',
            ],
            [
                'nome' => 'Enfermeiro',
                'codigo' => 'enfermeiro',
                'descricao' => 'Enfermeiro com acesso à triagem e alguns dados dos pacientes',
            ],
            [
                'nome' => 'Recepcionista',
                'codigo' => 'recepcionista',
                'descricao' => 'Recepcionista com acesso ao agendamento e cadastro de pacientes',
            ],
            [
                'nome' => 'Paciente',
                'codigo' => 'paciente',
                'descricao' => 'Paciente com acesso apenas aos seus próprios dados',
            ],
        ];

        foreach ($perfis as $perfil) {
            Perfil::create($perfil);
        }
    }
}