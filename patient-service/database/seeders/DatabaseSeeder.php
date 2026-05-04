<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeders do Patient Service...');
        $this->command->newLine();

        // Ordem de execução dos seeders
        $this->call([
            PacienteSeeder::class,          // 1. Criar pacientes primeiro (10 pacientes)
            ParenteSeeder::class,           // 2. Criar parentes dos pacientes (10 parentes)
            UtenteAutonomoSeeder::class,    // 3. Criar utentes autônomos (8 utentes)
            SolicitacaoExameSeeder::class,  // 5. Criar solicitações de exame (14 solicitações)
            SolicitacaoTriagemSeeder::class,           // 6. Criar triagens (12 triagens)
        ]);

        $this->command->newLine();
        $this->command->info('✅ Todos os seeders executados com sucesso!');
        $this->command->newLine();
        
        $this->command->info('📊 Resumo dos dados criados:');
        $this->command->info('   - 10 Pacientes');
        $this->command->info('   - 10 Parentes');
        $this->command->info('   - 8 Utentes Autônomos');
        $this->command->info('   - 20 Registros de Histórico');
        $this->command->info('   - 14 Solicitações de Exame');
        $this->command->info('   - 12 Triagens');
        $this->command->newLine();
        $this->command->info('🎉 Base de dados populada com sucesso!');
    }
}
