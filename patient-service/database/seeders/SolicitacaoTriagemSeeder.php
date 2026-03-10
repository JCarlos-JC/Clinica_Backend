<?php

namespace Database\Seeders;

use App\Models\SolicitacaoTriagem;
use App\Models\Paciente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SolicitacaoTriagemSeeder extends Seeder
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

        $solicitacoesTriagem = [
            // Triagens Normais (Verde)
            [
                'paciente_id' => $pacientes[0]->id ?? 1,
                'estados_urgencia_id' => 3, // Normal
                'data_triagem' => '2025-10-24 08:00:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => false,
                'resultados_exames' => null,
                'retorno_consulta' => false,
                'classificacao_risco' => 'verde',
                'prioridade_atendimento' => 3,
            ],
            [
                'paciente_id' => $pacientes[1]->id ?? 2,
                'estados_urgencia_id' => 3, // Normal
                'data_triagem' => '2025-10-24 09:15:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => false,
                'resultados_exames' => null,
                'retorno_consulta' => false,
                'classificacao_risco' => 'verde',
                'prioridade_atendimento' => 3,
            ],
            [
                'paciente_id' => $pacientes[4]->id ?? 5,
                'estados_urgencia_id' => 3, // Normal
                'data_triagem' => '2025-10-24 11:00:00',
                'status' => 'concluida',
                'ja_consultado' => false,
                'resultados_exames' => null,
                'retorno_consulta' => false,
                'classificacao_risco' => 'verde',
                'prioridade_atendimento' => 3,
            ],
            
            // Triagens Médias (Amarelo)
            [
                'paciente_id' => $pacientes[2]->id ?? 3,
                'estados_urgencia_id' => 2, // Urgente
                'data_triagem' => '2025-10-24 10:30:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => false,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '150/95 mmHg',
                    'Frequência Cardíaca' => '95 bpm',
                    'Temperatura' => '36.8°C',
                    'Saturação O2' => '96%',
                ]),
                'retorno_consulta' => false,
                'classificacao_risco' => 'amarelo',
                'prioridade_atendimento' => 2,
            ],
            [
                'paciente_id' => $pacientes[6]->id ?? 7,
                'estados_urgencia_id' => 2, // Urgente
                'data_triagem' => '2025-10-24 16:00:00',
                'status' => 'concluida',
                'ja_consultado' => false,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '140/90 mmHg',
                    'Frequência Cardíaca' => '88 bpm',
                    'Temperatura' => '37.2°C',
                    'Saturação O2' => '97%',
                ]),
                'retorno_consulta' => false,
                'classificacao_risco' => 'amarelo',
                'prioridade_atendimento' => 2,
            ],
            [
                'paciente_id' => $pacientes[7]->id ?? 8,
                'estados_urgencia_id' => 2, // Urgente
                'data_triagem' => '2025-10-24 17:30:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => false,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '130/85 mmHg',
                    'Frequência Cardíaca' => '92 bpm',
                    'Temperatura' => '37.5°C',
                    'Saturação O2' => '96%',
                ]),
                'retorno_consulta' => false,
                'classificacao_risco' => 'amarelo',
                'prioridade_atendimento' => 2,
            ],
            
            // Triagem Emergência (Vermelho)
            [
                'paciente_id' => $pacientes[3]->id ?? 4,
                'estados_urgencia_id' => 1, // Emergência
                'data_triagem' => '2025-10-24 14:45:00',
                'status' => 'concluida',
                'ja_consultado' => false,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '90/60 mmHg',
                    'Frequência Cardíaca' => '110 bpm',
                    'Temperatura' => '38.5°C',
                    'Saturação O2' => '94%',
                    'Observação' => 'EMERGÊNCIA - Suspeita de apendicite',
                ]),
                'retorno_consulta' => false,
                'classificacao_risco' => 'vermelho',
                'prioridade_atendimento' => 1,
            ],
            
            // Triagem Azul (Não Urgente)
            [
                'paciente_id' => $pacientes[8]->id ?? 9,
                'estados_urgencia_id' => 4, // Não urgente
                'data_triagem' => '2025-10-24 15:00:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => false,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '120/80 mmHg',
                    'Frequência Cardíaca' => '70 bpm',
                    'Temperatura' => '36.6°C',
                    'Saturação O2' => '98%',
                ]),
                'retorno_consulta' => false,
                'classificacao_risco' => 'azul',
                'prioridade_atendimento' => 4,
            ],
            
            // Retornos
            [
                'paciente_id' => $pacientes[0]->id ?? 1,
                'estados_urgencia_id' => 3, // Normal
                'data_triagem' => '2025-10-25 09:00:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => true,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '118/78 mmHg',
                    'Hemograma' => 'Anexo',
                    'Glicemia' => '92 mg/dL',
                ]),
                'retorno_consulta' => true,
                'classificacao_risco' => 'verde',
                'prioridade_atendimento' => 3,
            ],
            [
                'paciente_id' => $pacientes[2]->id ?? 3,
                'estados_urgencia_id' => 3, // Normal
                'data_triagem' => '2025-10-25 10:30:00',
                'status' => 'concluida',
                'ja_consultado' => true,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '135/88 mmHg',
                    'ECG' => 'Normal',
                    'Colesterol' => 'Resultados anexos',
                ]),
                'retorno_consulta' => true,
                'classificacao_risco' => 'verde',
                'prioridade_atendimento' => 3,
            ],
            [
                'paciente_id' => $pacientes[9]->id ?? 10,
                'estados_urgencia_id' => 3, // Normal
                'data_triagem' => '2025-10-25 14:00:00',
                'status' => 'aguardando_triagem',
                'ja_consultado' => false,
                'resultados_exames' => json_encode([
                    'Pressão Arterial' => '110/70 mmHg',
                    'Frequência Cardíaca' => '75 bpm',
                    'Temperatura' => '36.5°C',
                    'Saturação O2' => '99%',
                ]),
                'retorno_consulta' => false,
                'classificacao_risco' => 'verde',
                'prioridade_atendimento' => 3,
            ],
        ];

        foreach ($solicitacoesTriagem as $triagemData) {
            SolicitacaoTriagem::create($triagemData);
        }

        $this->command->info('✅ 12 solicitações de triagem criadas com sucesso!');
    }
}
