<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MedicosEspecialidadesSeeder extends Seeder
{
    /**
     * Seed médicos com especialidades
     */
    public function run(): void
    {
        $medicos = [
            [
                'nome' => 'Dr. Carlos Alberto Silva',
                'email' => 'carlos.silva@clinica.com',
                'cargo' => 'Médico Cardiologista',
                'especialidade' => 'Cardiologia'
            ],
            [
                'nome' => 'Dra. Ana Paula Costa',
                'email' => 'ana.costa@clinica.com',
                'cargo' => 'Médico Cardiologista',
                'especialidade' => 'Cardiologia'
            ],
            [
                'nome' => 'Dr. Roberto Oliveira',
                'email' => 'roberto.oliveira@clinica.com',
                'cargo' => 'Médico Ortopedista',
                'especialidade' => 'Ortopedia'
            ],
            [
                'nome' => 'Dra. Juliana Martins',
                'email' => 'juliana.martins@clinica.com',
                'cargo' => 'Médico Ortopedista',
                'especialidade' => 'Ortopedia'
            ],
            [
                'nome' => 'Dr. Fernando Santos',
                'email' => 'fernando.santos@clinica.com',
                'cargo' => 'Médico Pediatra',
                'especialidade' => 'Pediatria'
            ],
            [
                'nome' => 'Dra. Mariana Lima',
                'email' => 'mariana.lima@clinica.com',
                'cargo' => 'Médico Pediatra',
                'especialidade' => 'Pediatria'
            ],
            [
                'nome' => 'Dra. Patricia Almeida',
                'email' => 'patricia.almeida@clinica.com',
                'cargo' => 'Médico Ginecologista',
                'especialidade' => 'Ginecologia'
            ],
            [
                'nome' => 'Dra. Camila Rodrigues',
                'email' => 'camila.rodrigues@clinica.com',
                'cargo' => 'Médico Ginecologista',
                'especialidade' => 'Ginecologia'
            ],
            [
                'nome' => 'Dr. Pedro Henrique Souza',
                'email' => 'pedro.souza@clinica.com',
                'cargo' => 'Médico Dermatologista',
                'especialidade' => 'Dermatologia'
            ],
            [
                'nome' => 'Dra. Beatriz Fernandes',
                'email' => 'beatriz.fernandes@clinica.com',
                'cargo' => 'Médico Dermatologista',
                'especialidade' => 'Dermatologia'
            ],
            [
                'nome' => 'Dr. Ricardo Pereira',
                'email' => 'ricardo.pereira@clinica.com',
                'cargo' => 'Médico Oftalmologista',
                'especialidade' => 'Oftalmologia'
            ],
            [
                'nome' => 'Dra. Lucia Mendes',
                'email' => 'lucia.mendes@clinica.com',
                'cargo' => 'Médico Oftalmologista',
                'especialidade' => 'Oftalmologia'
            ],
            [
                'nome' => 'Dr. Marcos Antonio Dias',
                'email' => 'marcos.dias@clinica.com',
                'cargo' => 'Médico Neurologista',
                'especialidade' => 'Neurologia'
            ],
            [
                'nome' => 'Dra. Renata Barbosa',
                'email' => 'renata.barbosa@clinica.com',
                'cargo' => 'Médico Neurologista',
                'especialidade' => 'Neurologia'
            ],
            [
                'nome' => 'Dr. André Luis Castro',
                'email' => 'andre.castro@clinica.com',
                'cargo' => 'Médico Psiquiatra',
                'especialidade' => 'Psiquiatria'
            ],
            [
                'nome' => 'Dra. Cristina Moreira',
                'email' => 'cristina.moreira@clinica.com',
                'cargo' => 'Médico Psiquiatra',
                'especialidade' => 'Psiquiatria'
            ],
            [
                'nome' => 'Dr. Gabriel Azevedo',
                'email' => 'gabriel.azevedo@clinica.com',
                'cargo' => 'Médico Otorrinolaringologista',
                'especialidade' => 'Otorrinolaringologia'
            ],
            [
                'nome' => 'Dra. Vanessa Cardoso',
                'email' => 'vanessa.cardoso@clinica.com',
                'cargo' => 'Médico Otorrinolaringologista',
                'especialidade' => 'Otorrinolaringologia'
            ],
            [
                'nome' => 'Dr. José Roberto Campos',
                'email' => 'jose.campos@clinica.com',
                'cargo' => 'Médico Clínico Geral',
                'especialidade' => 'Clínica Geral'
            ],
            [
                'nome' => 'Dra. Sandra Regina Nunes',
                'email' => 'sandra.nunes@clinica.com',
                'cargo' => 'Médico Clínico Geral',
                'especialidade' => 'Clínica Geral'
            ],
        ];

        foreach ($medicos as $medicoData) {
            // Verifica se o médico já existe
            $exists = User::where('email', $medicoData['email'])->exists();
            
            if (!$exists) {
                User::create([
                    'nome' => $medicoData['nome'],
                    'email' => $medicoData['email'],
                    'senha' => Hash::make('medico123'),
                    'cargo' => $medicoData['cargo'],
                    'ativo' => true,
                ]);
                
                $this->command->info("Médico criado: {$medicoData['nome']} - {$medicoData['especialidade']}");
            } else {
                $this->command->warn("Médico já existe: {$medicoData['email']}");
            }
        }

        $this->command->info('Seeder de médicos com especialidades concluído!');
        $this->command->info('Senha padrão: medico123');
    }
}
