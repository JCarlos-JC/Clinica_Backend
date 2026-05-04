<?php

namespace Database\Seeders;

use App\Models\SolicitacaoExame;
use App\Models\Paciente;
use Illuminate\Database\Seeder;

class SolicitacaoExameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pacientes = Paciente::limit(10)->get();

        if ($pacientes->isEmpty()) {
            $this->command->warn('⚠️  Nenhum paciente encontrado. Execute PacienteSeeder primeiro.');
            return;
        }

        $solicitacoes = [
            // Paciente 1 - João Silva
            [
                'paciente_nid' => $pacientes[0]->nid ?? '0001/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 1, // ID do médico Dr. Manuel Costa
                'exames_solicitados' => json_encode(['Hemograma Completo', 'Glicemia em Jejum', 'Ureia']),
                'exames_realizaveis' => json_encode(['Hemograma Completo', 'Glicemia em Jejum', 'Ureia']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-10-15 09:00:00',
                'status' => 'pendente',
                'tipos_exame_id' => 1,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Exames de rotina anual',
            ],
            [
                'paciente_nid' => $pacientes[0]->nid ?? '0001/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 2, // ID do médico Dr. Carlos Mendes
                'exames_solicitados' => json_encode(['Raio-X Abdômen']),
                'exames_realizaveis' => json_encode(['Raio-X Abdômen']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-05-19 20:00:00',
                'status' => 'aceito',
                'tipos_exame_id' => 2,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Suspeita de apendicite - pré-cirúrgico',
            ],
            
            // Paciente 2 - Maria Santos
            [
                'paciente_nid' => $pacientes[1]->nid ?? '0002/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 3, // ID da médica Dra. Ana Ferreira
                'exames_solicitados' => json_encode(['Papanicolau', 'Mamografia']),
                'exames_realizaveis' => json_encode(['Papanicolau']),
                'exames_nao_realizaveis' => json_encode(['Mamografia']),
                'data_solicitacao' => '2025-03-05 10:30:00',
                'status' => 'concluido',
                'tipos_exame_id' => 3,
                'metodo_pagamento_id' => 1,
                'data_pagamento' => '2025-03-05 10:00:00',
                'observacoes' => 'Exames preventivos anuais - Mamografia encaminhada para clínica externa',
            ],
            
            // Paciente 3 - Carlos Mendes
            [
                'paciente_nid' => $pacientes[2]->nid ?? '0003/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 4, // ID do médico Dr. José Tavares
                'exames_solicitados' => json_encode(['Eletrocardiograma', 'Ecocardiograma', 'Holter 24h']),
                'exames_realizaveis' => json_encode(['Eletrocardiograma']),
                'exames_nao_realizaveis' => json_encode(['Ecocardiograma', 'Holter 24h']),
                'data_solicitacao' => '2025-04-12 15:00:00',
                'status' => 'em_laboratorio',
                'tipos_exame_id' => 4,
                'metodo_pagamento_id' => 2,
                'data_pagamento' => '2025-04-13 09:00:00',
                'observacoes' => 'Dores no peito - investigação cardíaca. Ecocardiograma e Holter encaminhados',
            ],
            [
                'paciente_nid' => $pacientes[2]->nid ?? '0003/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 4, // ID do médico Dr. José Tavares
                'exames_solicitados' => json_encode(['Colesterol Total', 'Triglicerídeos', 'HDL', 'LDL']),
                'exames_realizaveis' => json_encode(['Colesterol Total', 'Triglicerídeos', 'HDL', 'LDL']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-04-20 14:00:00',
                'status' => 'aceito',
                'tipos_exame_id' => 1,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Acompanhamento tratamento cardíaco',
            ],
            
            // Paciente 4 - Ana Costa
            [
                'paciente_nid' => $pacientes[3]->nid ?? '0004/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 5, // ID da médica Dra. Patricia Neves
                'exames_solicitados' => json_encode(['Biópsia de Pele']),
                'exames_realizaveis' => json_encode([]),
                'exames_nao_realizaveis' => json_encode(['Biópsia de Pele']),
                'data_solicitacao' => '2025-06-08 11:00:00',
                'status' => 'cancelado',
                'tipos_exame_id' => 5,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Biópsia não realizada na clínica - encaminhada para laboratório especializado',
            ],
            
            // Paciente 5 - Pedro Rodrigues
            [
                'paciente_nid' => $pacientes[4]->nid ?? '0005/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 6, // ID do médico Dr. Fernando Silva
                'exames_solicitados' => json_encode(['Raio-X Joelho Direito (AP + Perfil)', 'Ressonância Magnética Joelho']),
                'exames_realizaveis' => json_encode(['Raio-X Joelho Direito (AP + Perfil)']),
                'exames_nao_realizaveis' => json_encode(['Ressonância Magnética Joelho']),
                'data_solicitacao' => '2025-07-20 16:00:00',
                'status' => 'pago',
                'tipos_exame_id' => 2,
                'metodo_pagamento_id' => 1,
                'data_pagamento' => '2025-07-20 16:15:00',
                'observacoes' => 'Dor no joelho após trauma esportivo. RM encaminhada',
            ],
            
            // Paciente 6 - Isabel Fernandes
            [
                'paciente_nid' => $pacientes[5]->nid ?? '0006/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 7, // ID do médico Dr. Miguel Rocha
                'exames_solicitados' => json_encode(['Campimetria Visual', 'Tonometria', 'Fundo de Olho']),
                'exames_realizaveis' => json_encode(['Tonometria', 'Fundo de Olho']),
                'exames_nao_realizaveis' => json_encode(['Campimetria Visual']),
                'data_solicitacao' => '2025-08-10 10:00:00',
                'status' => 'concluido',
                'tipos_exame_id' => 6,
                'metodo_pagamento_id' => 2,
                'data_pagamento' => '2025-08-10 09:45:00',
                'observacoes' => 'Avaliação oftalmológica completa. Campimetria encaminhada',
            ],
            
            // Paciente 7 - Francisco Almeida
            [
                'paciente_nid' => $pacientes[6]->nid ?? '0007/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 1, // ID do médico Dr. Manuel Costa
                'exames_solicitados' => json_encode([
                    'Hemograma Completo',
                    'Glicemia',
                    'Ureia',
                    'Creatinina',
                    'Ácido Úrico',
                    'TGO',
                    'TGP',
                    'Colesterol Total',
                    'HDL',
                    'LDL',
                    'Triglicerídeos',
                ]),
                'exames_realizaveis' => json_encode([
                    'Hemograma Completo',
                    'Glicemia',
                    'Ureia',
                    'Creatinina',
                    'Ácido Úrico',
                    'TGO',
                    'TGP',
                    'Colesterol Total',
                    'HDL',
                    'LDL',
                    'Triglicerídeos',
                ]),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-09-05 08:00:00',
                'status' => 'concluido',
                'tipos_exame_id' => 1,
                'metodo_pagamento_id' => 1,
                'data_pagamento' => '2025-09-05 07:45:00',
                'observacoes' => 'Check-up anual executivo - todos os valores normais',
            ],
            
            // Paciente 8 - Beatriz Pereira
            [
                'paciente_nid' => $pacientes[7]->nid ?? '0008/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 8, // ID da médica Dra. Lucia Marques
                'exames_solicitados' => json_encode(['Hemograma', 'TSH', 'T4 Livre']),
                'exames_realizaveis' => json_encode(['Hemograma', 'TSH', 'T4 Livre']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-09-18 11:30:00',
                'status' => 'pendente',
                'tipos_exame_id' => 1,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Investigação de sintomas de ansiedade - avaliar função tireoidiana',
            ],
            
            // Paciente 9 - António Sousa
            [
                'paciente_nid' => $pacientes[8]->nid ?? '0009/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 9, // ID do médico Dr. Armando Dias
                'exames_solicitados' => json_encode(['Urinálise', 'Urocultura', 'PSA Total', 'Ultrassom Prostático']),
                'exames_realizaveis' => json_encode(['Urinálise', 'Urocultura', 'PSA Total', 'Ultrassom Prostático']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-10-01 15:30:00',
                'status' => 'em_laboratorio',
                'tipos_exame_id' => 7,
                'metodo_pagamento_id' => 2,
                'data_pagamento' => '2025-10-01 15:00:00',
                'observacoes' => 'Queixas urinárias - investigação prostática',
            ],
            
            // Paciente 10 - Teresa Martins
            [
                'paciente_nid' => $pacientes[9]->nid ?? '0010/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 3, // ID da médica Dra. Ana Ferreira
                'exames_solicitados' => json_encode(['Ultrassom Obstétrico', 'Hemograma', 'Glicemia', 'Grupo Sanguíneo + Rh', 'VDRL', 'HIV', 'Toxoplasmose IgG/IgM']),
                'exames_realizaveis' => json_encode(['Ultrassom Obstétrico', 'Hemograma', 'Glicemia', 'Grupo Sanguíneo + Rh', 'VDRL', 'HIV', 'Toxoplasmose IgG/IgM']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-10-10 10:00:00',
                'status' => 'aceito',
                'tipos_exame_id' => 8,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Exames pré-natais - 1º trimestre',
            ],
            
            // Solicitações adicionais
            [
                'paciente_nid' => $pacientes[1]->nid ?? '0002/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 10, // ID do médico Dr. Paulo Gomes
                'exames_solicitados' => json_encode(['Raio-X Tórax PA + Perfil']),
                'exames_realizaveis' => json_encode(['Raio-X Tórax PA + Perfil']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-10-20 18:00:00',
                'status' => 'pendente',
                'tipos_exame_id' => 2,
                'metodo_pagamento_id' => null,
                'data_pagamento' => null,
                'observacoes' => 'Tosse persistente há 2 semanas',
            ],
            [
                'paciente_nid' => $pacientes[4]->nid ?? '0005/2025',
                'utente_autonomo_nid' => null,
                'solicitante_id' => 11, // ID da fisioterapeuta Ft. Sandra Oliveira
                'exames_solicitados' => json_encode(['Avaliação Fisioterapêutica Completa']),
                'exames_realizaveis' => json_encode(['Avaliação Fisioterapêutica Completa']),
                'exames_nao_realizaveis' => json_encode([]),
                'data_solicitacao' => '2025-08-01 14:00:00',
                'status' => 'concluido',
                'tipos_exame_id' => 9,
                'metodo_pagamento_id' => 1,
                'data_pagamento' => '2025-08-01 13:45:00',
                'observacoes' => 'Primeira sessão de fisioterapia - Lesão ligamentar grau II',
            ],
        ];

        foreach ($solicitacoes as $solicitacaoData) {
            SolicitacaoExame::create($solicitacaoData);
        }

        $this->command->info('✅ 14 solicitações de exame criadas com sucesso!');
    }
}
