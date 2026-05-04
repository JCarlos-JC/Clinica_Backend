<?php

namespace Database\Seeders;

use App\Models\Parente;
use App\Models\Paciente;
use Illuminate\Database\Seeder;

class ParenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obter os primeiros pacientes para associar parentes
        $pacientes = Paciente::limit(10)->get();

        if ($pacientes->isEmpty()) {
            $this->command->warn('⚠️  Nenhum paciente encontrado. Execute PacienteSeeder primeiro.');
            return;
        }

        $parentes = [
            [
                'paciente_nid' => $pacientes[0]->nid ?? '0001/2025',
                'nome' => 'Maria Silva',
                'grau_parentesco_id' => 1, // esposa
                'celular' => '+258843987654',
                'celular_alternativo' => '+258823987654',
            ],
            [
                'paciente_nid' => $pacientes[1]->nid ?? '0002/2025',
                'nome' => 'Pedro Santos',
                'grau_parentesco_id' => 2, // irmao
                'celular' => '+258847654321',
                'celular_alternativo' => null,
            ],
            [
                'paciente_nid' => $pacientes[2]->nid ?? '0003/2025',
                'nome' => 'Ana Mendes',
                'grau_parentesco_id' => 3, // filha
                'celular' => '+258841234567',
                'celular_alternativo' => '+258821234567',
            ],
            [
                'paciente_nid' => $pacientes[3]->nid ?? '0004/2025',
                'nome' => 'Teresa Costa',
                'grau_parentesco_id' => 4, // mae
                'celular' => '+258848765432',
                'celular_alternativo' => null,
            ],
            [
                'paciente_nid' => $pacientes[4]->nid ?? '0005/2025',
                'nome' => 'Lucia Rodrigues',
                'grau_parentesco_id' => 1, // esposa
                'celular' => '+258843210987',
                'celular_alternativo' => '+258823210987',
            ],
            [
                'paciente_nid' => $pacientes[5]->nid ?? '0006/2025',
                'nome' => 'Manuel Fernandes',
                'grau_parentesco_id' => 5, // filho
                'celular' => '+258844567890',
                'celular_alternativo' => null,
            ],
            [
                'paciente_nid' => $pacientes[6]->nid ?? '0007/2025',
                'nome' => 'Rosa Almeida',
                'grau_parentesco_id' => 1, // esposa
                'celular' => '+258845678901',
                'celular_alternativo' => '+258825678901',
            ],
            [
                'paciente_nid' => $pacientes[7]->nid ?? '0008/2025',
                'nome' => 'Joana Pereira',
                'grau_parentesco_id' => 6, // irma
                'celular' => '+258846789012',
                'celular_alternativo' => null,
            ],
            [
                'paciente_nid' => $pacientes[8]->nid ?? '0009/2025',
                'nome' => 'Carla Sousa',
                'grau_parentesco_id' => 7, // ex_esposa
                'celular' => '+258847890123',
                'celular_alternativo' => null,
            ],
            [
                'paciente_nid' => $pacientes[9]->nid ?? '0010/2025',
                'nome' => 'Miguel Martins',
                'grau_parentesco_id' => 8, // esposo
                'celular' => '+258848901234',
                'celular_alternativo' => '+258828901234',
            ],
        ];

        foreach ($parentes as $parenteData) {
            Parente::create($parenteData);
        }

        $this->command->info('✅ 10 parentes criados com sucesso!');
    }
}
