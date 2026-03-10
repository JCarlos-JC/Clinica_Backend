<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrecoConsultaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar se já existem dados
        if (DB::table('precos_consultas')->count() > 0) {
            $this->command->info('Tabela de preços de consultas já possui dados. Pulando...');
            return;
        }
        
        $precos = [
            // Clínica Geral (tipo_consulta_id: 1)
            [
                'tipo_consulta_id' => 1,
                'tipo_utente_id' => 1, // Funcionário
                'valor' => 300.00,
                'descricao' => 'Consulta de Clínica Geral para Funcionário',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_consulta_id' => 1,
                'tipo_utente_id' => 2, // Estudante
                'valor' => 500.00,
                'descricao' => 'Consulta de Clínica Geral para Estudante',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_consulta_id' => 1,
                'tipo_utente_id' => 3, // Externo
                'valor' => 1000.00,
                'descricao' => 'Consulta de Clínica Geral para Externo',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Consulta Especializada (tipo_consulta_id: 2)
            [
                'tipo_consulta_id' => 2,
                'tipo_utente_id' => 1, // Funcionário
                'valor' => 500.00,
                'descricao' => 'Consulta Especializada para Funcionário',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_consulta_id' => 2,
                'tipo_utente_id' => 2, // Estudante
                'valor' => 800.00,
                'descricao' => 'Consulta Especializada para Estudante',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_consulta_id' => 2,
                'tipo_utente_id' => 3, // Externo
                'valor' => 1500.00,
                'descricao' => 'Consulta Especializada para Externo',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Consulta de Urgência (tipo_consulta_id: 3)
            [
                'tipo_consulta_id' => 3,
                'tipo_utente_id' => 1, // Funcionário
                'valor' => 800.00,
                'descricao' => 'Consulta de Urgência para Funcionário',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_consulta_id' => 3,
                'tipo_utente_id' => 2, // Estudante
                'valor' => 1200.00,
                'descricao' => 'Consulta de Urgência para Estudante',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_consulta_id' => 3,
                'tipo_utente_id' => 3, // Externo
                'valor' => 2000.00,
                'descricao' => 'Consulta de Urgência para Externo',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('precos_consultas')->insert($precos);
        
        $this->command->info('✅ ' . count($precos) . ' preços de consultas criados com sucesso!');
    }
}
