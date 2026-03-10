<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Executar os seeders na ordem correta para manter a integridade referencial
        $this->call([
            PerfilSeeder::class,
            PermissaoSeeder::class,
            UserSeeder::class,
            MedicosEspecialidadesSeeder::class,
            PerfilPermissaoSeeder::class,
            UserPerfilSeeder::class,
            LogAutenticacaoSeeder::class,
        ]);
    }
}
