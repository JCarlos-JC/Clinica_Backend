<?php

namespace Database\Seeders;

use App\Models\UtenteAutonomo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UtenteAutonomoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $utentes = [
            [
                'nome' => 'Ricardo',
                'apelido' => 'Neves',
                'tipo_documento_id' => 1,
                'celular' => '+258844445555',
                'celular_alternativo' => '+258824445555',
                'email' => 'ricardo.neves@email.com',
                'hospital_proveniencia' => 'Hospital Central de Maputo',
                'exames_solicitados' => json_encode(['Hemograma Completo', 'Glicemia em Jejum']),
                'data_solicitacao' => '2025-10-20',
                'status' => 'pendente',
                'tipos_exame_id' => 1,
                'observacoes' => 'Paciente com sintomas de anemia',
            ],
            [
                'nome' => 'Sandra',
                'apelido' => 'Oliveira',
                'tipo_documento_id' => 2,
                'celular' => '+258845556666',
                'celular_alternativo' => null,
                'email' => null,
                'hospital_proveniencia' => 'Hospital Provincial de Nampula',
                'exames_solicitados' => json_encode(['Raio-X Tórax', 'Teste de COVID-19']),
                'data_solicitacao' => '2025-10-21',
                'status' => 'aceito',
                'tipos_exame_id' => 2,
                'observacoes' => 'Suspeita de pneumonia',
            ],
            [
                'nome' => 'Armando',
                'apelido' => 'Machado',
                'tipo_documento_id' => 1,
                'celular' => '+258846667777',
                'celular_alternativo' => '+258826667777',
                'email' => 'armando.machado@email.com',
                'hospital_proveniencia' => 'Hospital Geral da Beira',
                'exames_solicitados' => json_encode(['Ultrassom Abdominal', 'Hemograma']),
                'data_solicitacao' => '2025-10-22',
                'status' => 'pago',
                'tipos_exame_id' => 3,
                'metodo_pagamento_id' => 1,
                'data_pagamento' => '2025-10-23',
                'observacoes' => 'Dor abdominal persistente',
            ],
            [
                'nome' => 'Claudia',
                'apelido' => 'Ferreira',
                'tipo_documento_id' => 1,
                'celular' => '+258847778888',
                'celular_alternativo' => null,
                'email' => 'claudia.ferreira@email.com',
                'hospital_proveniencia' => 'Hospital Central de Maputo',
                'exames_solicitados' => json_encode(['Mamografia', 'Papanicolau']),
                'data_solicitacao' => '2025-10-19',
                'status' => 'pago_laboratorio',
                'tipos_exame_id' => 4,
                'metodo_pagamento_id' => 2,
                'data_pagamento' => '2025-10-20',
                'observacoes' => 'Exames de rotina preventivos',
            ],
            [
                'nome' => 'Domingos',
                'apelido' => 'Ramos',
                'tipo_documento_id' => 1,
                'celular' => '+258848889999',
                'celular_alternativo' => '+258828889999',
                'email' => 'domingos.ramos@email.com',
                'hospital_proveniencia' => 'Hospital Provincial de Tete',
                'exames_solicitados' => json_encode(['Eletrocardiograma', 'Ecocardiograma', 'Teste Ergométrico']),
                'data_solicitacao' => '2025-10-18',
                'status' => 'processando',
                'tipos_exame_id' => 5,
                'metodo_pagamento_id' => 1,
                'data_pagamento' => '2025-10-19',
                'data_exames' => '2025-10-24',
                'observacoes' => 'Histórico de problemas cardíacos',
            ],
            [
                'nome' => 'Elisa',
                'apelido' => 'Monteiro',
                'tipo_documento_id' => 2,
                'celular' => '+258849990000',
                'celular_alternativo' => null,
                'email' => 'elisa.monteiro@email.com',
                'hospital_proveniencia' => 'Hospital Geral de Quelimane',
                'exames_solicitados' => json_encode(['Colesterol Total', 'Triglicerídeos', 'HDL', 'LDL']),
                'data_solicitacao' => '2025-10-15',
                'status' => 'concluido',
                'tipos_exame_id' => 1,
                'metodo_pagamento_id' => 2,
                'data_pagamento' => '2025-10-16',
                'data_exames' => '2025-10-17',
                'resultados_exames' => json_encode([
                    'Colesterol Total' => '220 mg/dL',
                    'Triglicerídeos' => '180 mg/dL',
                    'HDL' => '45 mg/dL',
                    'LDL' => '140 mg/dL',
                ]),
                'data_resultados' => '2025-10-18',
                'historico_exames' => json_encode([
                    ['data' => '2025-10-17', 'exame' => 'Colesterol Total', 'resultado' => '220 mg/dL'],
                ]),
                'observacoes' => 'Níveis ligeiramente elevados',
            ],
            [
                'nome' => 'Fernando',
                'apelido' => 'Tavares',
                'tipo_documento_id' => 1,
                'celular' => '+258840001111',
                'celular_alternativo' => '+258820001111',
                'email' => null,
                'hospital_proveniencia' => 'Hospital Provincial de Gaza',
                'exames_solicitados' => json_encode(['Teste de Malária', 'Hemograma']),
                'data_solicitacao' => '2025-10-23',
                'status' => 'pendente',
                'tipos_exame_id' => 2,
                'observacoes' => 'Febre alta há 3 dias',
            ],
            [
                'nome' => 'Graça',
                'apelido' => 'Simões',
                'tipo_documento_id' => 1,
                'celular' => '+258841112222',
                'celular_alternativo' => null,
                'email' => 'graca.simoes@email.com',
                'hospital_proveniencia' => 'Hospital Central de Maputo',
                'exames_solicitados' => json_encode(['Urinálise', 'Urocultura']),
                'data_solicitacao' => '2025-10-22',
                'status' => 'aceito',
                'tipos_exame_id' => 1,
                'observacoes' => 'Suspeita de infecção urinária',
            ],
        ];

        foreach ($utentes as $utenteData) {
            UtenteAutonomo::create($utenteData);
        }

        $this->command->info('✅ 8 utentes autônomos criados com sucesso!');
    }
}
