<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedRacas();
        $this->seedProvincias();
        $this->seedDistritos();
        $this->seedBairros();
        $this->seedFormasMedicamento();
        $this->seedViasAdministracao();
        $this->seedMedicamentos();
        $this->seedTipoDocumentos();
        $this->seedTipoUtentes();
        $this->seedGrauParentesco();
        $this->seedUnidadesOrganica();
        $this->seedMetodosPagamento();
        $this->seedEspecialidades();
        $this->seedTiposConsulta();
        $this->seedFuncaoEspecialidades();
        $this->seedEstadosConsulta();
        $this->seedClassificacaoRisco();
        $this->seedEstadosUrgencia();
        $this->seedTiposExame();
    }

    private function seedRacas(): void
    {
        if (DB::table('racas')->count() > 0) {
            $this->command->info('Tabela de raças já possui dados. Pulando...');
            return;
        }

        DB::table('racas')->insert([
            ['nome' => 'Negra', 'codigo' => 'NEG', 'descricao' => 'Raça negra', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Branca', 'codigo' => 'BRA', 'descricao' => 'Raça branca', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Parda', 'codigo' => 'PAR', 'descricao' => 'Raça parda', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Amarela', 'codigo' => 'AMA', 'descricao' => 'Raça amarela', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Indígena', 'codigo' => 'IND', 'descricao' => 'Raça indígena', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Outra', 'codigo' => 'OUT', 'descricao' => 'Outra raça', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Dados de raças inseridos com sucesso!');
    }

    private function seedProvincias(): void
    {
        if (DB::table('provincias')->count() > 0) {
            $this->command->info('Tabela de províncias já possui dados. Pulando...');
            return;
        }

        DB::table('provincias')->insert([
            ['nome' => 'Maputo Cidade', 'codigo' => 'MPM', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Maputo Província', 'codigo' => 'MPP', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Gaza', 'codigo' => 'GAZ', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Inhambane', 'codigo' => 'INH', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Sofala', 'codigo' => 'SOF', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Manica', 'codigo' => 'MAN', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Tete', 'codigo' => 'TET', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Zambézia', 'codigo' => 'ZAM', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Nampula', 'codigo' => 'NAM', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Cabo Delgado', 'codigo' => 'CAD', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Niassa', 'codigo' => 'NIA', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Dados de províncias inseridos com sucesso!');
    }

    private function seedDistritos(): void
    {
        if (DB::table('distritos')->count() > 0) {
            $this->command->info('Tabela de distritos já possui dados. Pulando...');
            return;
        }

        // Obter IDs das províncias
        $maputoCidade = DB::table('provincias')->where('codigo', 'MPM')->first()->id;
        $maputoProvincia = DB::table('provincias')->where('codigo', 'MPP')->first()->id;
        $gaza = DB::table('provincias')->where('codigo', 'GAZ')->first()->id;

        // Distritos de Maputo Cidade
        $distritosMC = [
            ['nome' => 'KaMpfumu', 'codigo' => 'KMP', 'provincia_id' => $maputoCidade, 'ativo' => true],
            ['nome' => 'Nlhamankulu', 'codigo' => 'NLH', 'provincia_id' => $maputoCidade, 'ativo' => true],
            ['nome' => 'KaMaxakeni', 'codigo' => 'KMX', 'provincia_id' => $maputoCidade, 'ativo' => true],
            ['nome' => 'KaMavota', 'codigo' => 'KMV', 'provincia_id' => $maputoCidade, 'ativo' => true],
            ['nome' => 'KaMubukwana', 'codigo' => 'KMB', 'provincia_id' => $maputoCidade, 'ativo' => true],
            ['nome' => 'KaTembe', 'codigo' => 'KTM', 'provincia_id' => $maputoCidade, 'ativo' => true],
            ['nome' => 'KaNyaka', 'codigo' => 'KNY', 'provincia_id' => $maputoCidade, 'ativo' => true],
        ];

        // Distritos de Maputo Província
        $distritosMP = [
            ['nome' => 'Matola', 'codigo' => 'MAT', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Boane', 'codigo' => 'BOA', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Marracuene', 'codigo' => 'MRC', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Manhiça', 'codigo' => 'MNH', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Magude', 'codigo' => 'MAG', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Moamba', 'codigo' => 'MOA', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Namaacha', 'codigo' => 'NAA', 'provincia_id' => $maputoProvincia, 'ativo' => true],
            ['nome' => 'Matutuíne', 'codigo' => 'MTT', 'provincia_id' => $maputoProvincia, 'ativo' => true],
        ];

        // Distritos de Gaza
        $distritosGZ = [
            ['nome' => 'Xai-Xai', 'codigo' => 'XAI', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Bilene', 'codigo' => 'BIL', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Chibuto', 'codigo' => 'CHB', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Chicualacuala', 'codigo' => 'CHC', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Chigubo', 'codigo' => 'CHG', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Chókwè', 'codigo' => 'CHK', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Guijá', 'codigo' => 'GUI', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Mabalane', 'codigo' => 'MBL', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Manjacaze', 'codigo' => 'MNJ', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Massangena', 'codigo' => 'MSG', 'provincia_id' => $gaza, 'ativo' => true],
            ['nome' => 'Massingir', 'codigo' => 'MSS', 'provincia_id' => $gaza, 'ativo' => true],
        ];

        // Adicionar timestamps
        $timestamp = ['created_at' => now(), 'updated_at' => now()];
        $distritosMC = array_map(function ($distrito) use ($timestamp) {
            return array_merge($distrito, $timestamp);
        }, $distritosMC);
        
        $distritosMP = array_map(function ($distrito) use ($timestamp) {
            return array_merge($distrito, $timestamp);
        }, $distritosMP);
        
        $distritosGZ = array_map(function ($distrito) use ($timestamp) {
            return array_merge($distrito, $timestamp);
        }, $distritosGZ);

        // Inserir dados
        DB::table('distritos')->insert(array_merge($distritosMC, $distritosMP, $distritosGZ));

        $this->command->info('Dados de distritos inseridos com sucesso!');
    }

    private function seedBairros(): void
    {
        if (DB::table('bairros')->count() > 0) {
            $this->command->info('Tabela de bairros já possui dados. Pulando...');
            return;
        }

        // Obter ID de alguns distritos
        $kaMpfumu = DB::table('distritos')->where('nome', 'KaMpfumu')->first()->id;
        $matola = DB::table('distritos')->where('nome', 'Matola')->first()->id;

        // Bairros de KaMpfumu
        $bairrosKaMpfumu = [
            ['nome' => 'Central', 'distrito_id' => $kaMpfumu, 'codigo' => 'B001', 'codigo_postal' => '1100', 'ativo' => true],
            ['nome' => 'Polana Cimento A', 'distrito_id' => $kaMpfumu, 'codigo' => 'B002', 'codigo_postal' => '1110', 'ativo' => true],
            ['nome' => 'Polana Cimento B', 'distrito_id' => $kaMpfumu, 'codigo' => 'B003', 'codigo_postal' => '1111', 'ativo' => true],
            ['nome' => 'Sommerschield', 'distrito_id' => $kaMpfumu, 'codigo' => 'B004', 'codigo_postal' => '1120', 'ativo' => true],
            ['nome' => 'Malhangalene', 'distrito_id' => $kaMpfumu, 'codigo' => 'B005', 'codigo_postal' => '1130', 'ativo' => true],
            ['nome' => 'Alto Maé', 'distrito_id' => $kaMpfumu, 'codigo' => 'B006', 'codigo_postal' => '1140', 'ativo' => true],
            ['nome' => 'Bairro do Museu', 'distrito_id' => $kaMpfumu, 'codigo' => 'B007', 'codigo_postal' => '1150', 'ativo' => true],
            ['nome' => 'Coop', 'distrito_id' => $kaMpfumu, 'codigo' => 'B008', 'codigo_postal' => '1160', 'ativo' => true],
        ];

        // Bairros da Matola
        $bairrosMatola = [
            ['nome' => 'Matola A', 'distrito_id' => $matola, 'codigo' => 'B101', 'codigo_postal' => '1500', 'ativo' => true],
            ['nome' => 'Matola B', 'distrito_id' => $matola, 'codigo' => 'B102', 'codigo_postal' => '1501', 'ativo' => true],
            ['nome' => 'Matola C', 'distrito_id' => $matola, 'codigo' => 'B103', 'codigo_postal' => '1502', 'ativo' => true],
            ['nome' => 'Matola D', 'distrito_id' => $matola, 'codigo' => 'B104', 'codigo_postal' => '1503', 'ativo' => true],
            ['nome' => 'Matola F', 'distrito_id' => $matola, 'codigo' => 'B105', 'codigo_postal' => '1504', 'ativo' => true],
            ['nome' => 'Matola G', 'distrito_id' => $matola, 'codigo' => 'B106', 'codigo_postal' => '1505', 'ativo' => true],
            ['nome' => 'Matola H', 'distrito_id' => $matola, 'codigo' => 'B107', 'codigo_postal' => '1506', 'ativo' => true],
            ['nome' => 'Matola J', 'distrito_id' => $matola, 'codigo' => 'B108', 'codigo_postal' => '1507', 'ativo' => true],
        ];

        // Adicionar timestamps
        $timestamp = ['created_at' => now(), 'updated_at' => now()];
        $bairrosKaMpfumu = array_map(function ($bairro) use ($timestamp) {
            return array_merge($bairro, $timestamp);
        }, $bairrosKaMpfumu);
        
        $bairrosMatola = array_map(function ($bairro) use ($timestamp) {
            return array_merge($bairro, $timestamp);
        }, $bairrosMatola);

        // Inserir dados
        DB::table('bairros')->insert(array_merge($bairrosKaMpfumu, $bairrosMatola));

        $this->command->info('Dados de bairros inseridos com sucesso!');
    }

    private function seedFormasMedicamento(): void
    {
        if (DB::table('formas_medicamento')->count() > 0) {
            $this->command->info('Tabela de formas de medicamento já possui dados. Pulando...');
            return;
        }

        DB::table('formas_medicamento')->insert([
            ['nome' => 'Comprimido', 'codigo' => 'COMP', 'descricao' => 'Medicamento em forma de comprimido', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Cápsula', 'codigo' => 'CAPS', 'descricao' => 'Medicamento em forma de cápsula', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Xarope', 'codigo' => 'XARP', 'descricao' => 'Medicamento em forma de xarope', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Injetável', 'codigo' => 'INJT', 'descricao' => 'Medicamento em forma injetável', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Pomada', 'codigo' => 'POMA', 'descricao' => 'Medicamento em forma de pomada', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Gotas', 'codigo' => 'GOTA', 'descricao' => 'Medicamento em forma de gotas', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Spray', 'codigo' => 'SPRY', 'descricao' => 'Medicamento em forma de spray', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Solução', 'codigo' => 'SOLC', 'descricao' => 'Medicamento em forma de solução', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Suspensão', 'codigo' => 'SUSP', 'descricao' => 'Medicamento em forma de suspensão', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Creme', 'codigo' => 'CREM', 'descricao' => 'Medicamento em forma de creme', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Dados de formas de medicamento inseridos com sucesso!');
    }

    private function seedViasAdministracao(): void
    {
        if (DB::table('vias_administracao')->count() > 0) {
            $this->command->info('Tabela de vias de administração já possui dados. Pulando...');
            return;
        }

        DB::table('vias_administracao')->insert([
            ['nome' => 'Oral', 'codigo' => 'OR', 'descricao' => 'Via de administração oral', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Intravenosa', 'codigo' => 'IV', 'descricao' => 'Via de administração intravenosa', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Intramuscular', 'codigo' => 'IM', 'descricao' => 'Via de administração intramuscular', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Subcutânea', 'codigo' => 'SC', 'descricao' => 'Via de administração subcutânea', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Tópica', 'codigo' => 'TP', 'descricao' => 'Via de administração tópica', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Ocular', 'codigo' => 'OC', 'descricao' => 'Via de administração ocular', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Nasal', 'codigo' => 'NS', 'descricao' => 'Via de administração nasal', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Auricular', 'codigo' => 'AU', 'descricao' => 'Via de administração auricular', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Retal', 'codigo' => 'RT', 'descricao' => 'Via de administração retal', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Vaginal', 'codigo' => 'VG', 'descricao' => 'Via de administração vaginal', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Dados de vias de administração inseridos com sucesso!');
    }

    private function seedMedicamentos(): void
    {
        if (DB::table('medicamentos')->count() > 0) {
            $this->command->info('Tabela de medicamentos já possui dados. Pulando...');
            return;
        }

        // Obter IDs das formas e vias de administração
        $comprimido = DB::table('formas_medicamento')->where('codigo', 'COMP')->first()->id;
        $xarope = DB::table('formas_medicamento')->where('codigo', 'XARP')->first()->id;
        $injetavel = DB::table('formas_medicamento')->where('codigo', 'INJT')->first()->id;
        $pomada = DB::table('formas_medicamento')->where('codigo', 'POMA')->first()->id;
        $gotas = DB::table('formas_medicamento')->where('codigo', 'GOTA')->first()->id;

        $oral = DB::table('vias_administracao')->where('codigo', 'OR')->first()->id;
        $intravenosa = DB::table('vias_administracao')->where('codigo', 'IV')->first()->id;
        $intramuscular = DB::table('vias_administracao')->where('codigo', 'IM')->first()->id;
        $topica = DB::table('vias_administracao')->where('codigo', 'TP')->first()->id;
        $ocular = DB::table('vias_administracao')->where('codigo', 'OC')->first()->id;

        DB::table('medicamentos')->insert([
            [
                'nome' => 'Paracetamol',
                'principio_ativo' => 'Paracetamol',
                'codigo' => 'MED001',
                'forma_id' => $comprimido,
                'via_administracao_id' => $oral,
                'dosagem' => '500',
                'unidade_dosagem' => 'mg',
                'instrucoes_padrao' => 'Tomar 1 comprimido de 8 em 8 horas.',
                'contraindicacoes' => 'Alergia ao paracetamol, doença hepática grave.',
                'efeitos_colaterais' => 'Reações alérgicas, alterações hematológicas, hepatotoxicidade em doses elevadas.',
                'controlado' => false,
                'generico' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Dipirona',
                'principio_ativo' => 'Metamizol sódico',
                'codigo' => 'MED002',
                'forma_id' => $comprimido,
                'via_administracao_id' => $oral,
                'dosagem' => '500',
                'unidade_dosagem' => 'mg',
                'instrucoes_padrao' => 'Tomar 1 comprimido de 6 em 6 horas.',
                'contraindicacoes' => 'Alergia à dipirona, pacientes com agranulocitose.',
                'efeitos_colaterais' => 'Reações anafiláticas, agranulocitose, leucopenia.',
                'controlado' => false,
                'generico' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Amoxicilina',
                'principio_ativo' => 'Amoxicilina',
                'codigo' => 'MED003',
                'forma_id' => $comprimido,
                'via_administracao_id' => $oral,
                'dosagem' => '500',
                'unidade_dosagem' => 'mg',
                'instrucoes_padrao' => 'Tomar 1 comprimido de 8 em 8 horas por 7 dias.',
                'contraindicacoes' => 'Alergia a penicilinas, mononucleose infecciosa.',
                'efeitos_colaterais' => 'Diarreia, náusea, erupções cutâneas, candidíase.',
                'controlado' => false,
                'generico' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Ibuprofeno',
                'principio_ativo' => 'Ibuprofeno',
                'codigo' => 'MED004',
                'forma_id' => $comprimido,
                'via_administracao_id' => $oral,
                'dosagem' => '400',
                'unidade_dosagem' => 'mg',
                'instrucoes_padrao' => 'Tomar 1 comprimido de 8 em 8 horas após as refeições.',
                'contraindicacoes' => 'Úlcera péptica, hipersensibilidade aos AINEs, insuficiência renal grave.',
                'efeitos_colaterais' => 'Dispepsia, diarreia, náuseas, dor abdominal, cefaleia.',
                'controlado' => false,
                'generico' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Penicilina Benzatina',
                'principio_ativo' => 'Benzilpenicilina benzatina',
                'codigo' => 'MED005',
                'forma_id' => $injetavel,
                'via_administracao_id' => $intramuscular,
                'dosagem' => '1.200.000',
                'unidade_dosagem' => 'UI',
                'instrucoes_padrao' => 'Administrar via intramuscular profunda.',
                'contraindicacoes' => 'Hipersensibilidade a penicilinas.',
                'efeitos_colaterais' => 'Reações alérgicas, dor no local da aplicação.',
                'controlado' => false,
                'generico' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de medicamentos inseridos com sucesso!');
    }

    private function seedTipoDocumentos(): void
    {
        if (DB::table('tipo_documentos')->count() > 0) {
            $this->command->info('Tabela de tipos de documento já possui dados. Pulando...');
            return;
        }

        DB::table('tipo_documentos')->insert([
            [
                'nome' => 'Bilhete de Identidade',
                'codigo' => 'BI',
                'descricao' => 'Bilhete de Identidade Nacional',
                'formato_validacao' => '/^[0-9]{12}[A-Z]$/',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Passaporte',
                'codigo' => 'PASS',
                'descricao' => 'Passaporte Nacional',
                'formato_validacao' => '/^[A-Z]{2}[0-9]{6}$/',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Cartão de Residência',
                'codigo' => 'CR',
                'descricao' => 'Cartão de Residência para Estrangeiros',
                'formato_validacao' => '/^[A-Z][0-9]{8}$/',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'DIRE',
                'codigo' => 'DIRE',
                'descricao' => 'Documento de Identificação e Residência para Estrangeiros',
                'formato_validacao' => '/^[0-9]{9}$/',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Certidão de Nascimento',
                'codigo' => 'CN',
                'descricao' => 'Certidão de Nascimento para Menores',
                'formato_validacao' => null,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Outro',
                'codigo' => 'OUT',
                'descricao' => 'Outro tipo de documento de identificação',
                'formato_validacao' => null,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de tipos de documento inseridos com sucesso!');
    }

    private function seedTipoUtentes(): void
    {
        if (DB::table('tipo_utentes')->count() > 0) {
            $this->command->info('Tabela de tipos de utente já possui dados. Pulando...');
            return;
        }

        DB::table('tipo_utentes')->insert([
            [
                'nome' => 'Estudante Não Bolseiro',
                'codigo' => 'EST-NB',
                'descricao' => 'Estudante universitário sem bolsa',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Estudante Bolseiro',
                'codigo' => 'EST-B',
                'descricao' => 'Estudante universitário com bolsa',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Estudante Mestrado',
                'codigo' => 'EST-M',
                'descricao' => 'Estudante de mestrado',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Estudante Doutoramento',
                'codigo' => 'EST-D',
                'descricao' => 'Estudante de doutoramento',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Docente',
                'codigo' => 'DOC',
                'descricao' => 'Professor universitário',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Funcionário',
                'codigo' => 'FUNC',
                'descricao' => 'Funcionário da universidade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Comunidade',
                'codigo' => 'COM',
                'descricao' => 'Membro da comunidade sem vínculo com a universidade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Familiar Docente',
                'codigo' => 'FAM-DOC',
                'descricao' => 'Familiar de docente',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Familiar Funcionário',
                'codigo' => 'FAM-FUNC',
                'descricao' => 'Familiar de funcionário',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de tipos de utente inseridos com sucesso!');
    }

    private function seedGrauParentesco(): void
    {
        if (DB::table('grau_parentesco')->count() > 0) {
            $this->command->info('Tabela de graus de parentesco já possui dados. Pulando...');
            return;
        }

        DB::table('grau_parentesco')->insert([
            ['nome' => 'Pai', 'codigo' => 'PAI', 'descricao' => 'Pai/Genitor', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Mãe', 'codigo' => 'MAE', 'descricao' => 'Mãe/Genitora', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Filho/Filha', 'codigo' => 'FILH', 'descricao' => 'Filho ou Filha', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Irmão/Irmã', 'codigo' => 'IRM', 'descricao' => 'Irmão ou Irmã', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Cônjuge', 'codigo' => 'CONJ', 'descricao' => 'Esposo ou Esposa', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Avô/Avó', 'codigo' => 'AVO', 'descricao' => 'Avô ou Avó', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Neto/Neta', 'codigo' => 'NETO', 'descricao' => 'Neto ou Neta', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Tio/Tia', 'codigo' => 'TIO', 'descricao' => 'Tio ou Tia', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Sobrinho/Sobrinha', 'codigo' => 'SOBR', 'descricao' => 'Sobrinho ou Sobrinha', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Primo/Prima', 'codigo' => 'PRIM', 'descricao' => 'Primo ou Prima', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Outro', 'codigo' => 'OUTRO', 'descricao' => 'Outro parentesco', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Dados de graus de parentesco inseridos com sucesso!');
    }

    private function seedUnidadesOrganica(): void
    {
        if (DB::table('unidades_organica')->count() > 0) {
            $this->command->info('Tabela de unidades orgânicas já possui dados. Pulando...');
            return;
        }

        DB::table('unidades_organica')->insert([
            [
                'nome' => 'Faculdade de Medicina',
                'sigla' => 'FM',
                'descricao' => 'Faculdade de Medicina da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Direito',
                'sigla' => 'FD',
                'descricao' => 'Faculdade de Direito da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Engenharia',
                'sigla' => 'FENG',
                'descricao' => 'Faculdade de Engenharia da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Educação',
                'sigla' => 'FACED',
                'descricao' => 'Faculdade de Educação da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Veterinária',
                'sigla' => 'FAVET',
                'descricao' => 'Faculdade de Veterinária da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Agronomia e Engenharia Florestal',
                'sigla' => 'FAEF',
                'descricao' => 'Faculdade de Agronomia e Engenharia Florestal da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Economia',
                'sigla' => 'FEC',
                'descricao' => 'Faculdade de Economia da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Letras e Ciências Sociais',
                'sigla' => 'FLCS',
                'descricao' => 'Faculdade de Letras e Ciências Sociais da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Faculdade de Ciências',
                'sigla' => 'FC',
                'descricao' => 'Faculdade de Ciências da UEM',
                'tipo' => 'faculdade',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Escola Superior de Hotelaria e Turismo',
                'sigla' => 'ESHTI',
                'descricao' => 'Escola Superior de Hotelaria e Turismo da UEM',
                'tipo' => 'escola',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Escola de Comunicação e Artes',
                'sigla' => 'ECA',
                'descricao' => 'Escola de Comunicação e Artes da UEM',
                'tipo' => 'escola',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Escola Superior de Ciências do Desporto',
                'sigla' => 'ESCIDE',
                'descricao' => 'Escola Superior de Ciências do Desporto da UEM',
                'tipo' => 'escola',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de unidades orgânicas inseridos com sucesso!');
    }

    private function seedMetodosPagamento(): void
    {
        if (DB::table('metodos_pagamento')->count() > 0) {
            $this->command->info('Tabela de métodos de pagamento já possui dados. Pulando...');
            return;
        }

        DB::table('metodos_pagamento')->insert([
            [
                'nome' => 'Dinheiro',
                'codigo' => 'CASH',
                'descricao' => 'Pagamento em dinheiro físico',
                'requer_comprovante' => false,
                'requer_confirmacao' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'M-Pesa',
                'codigo' => 'MPESA',
                'descricao' => 'Pagamento via M-Pesa',
                'requer_comprovante' => true,
                'requer_confirmacao' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'E-mola',
                'codigo' => 'EMOLA',
                'descricao' => 'Pagamento via E-mola',
                'requer_comprovante' => true,
                'requer_confirmacao' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Cartão de Crédito/Débito',
                'codigo' => 'CARD',
                'descricao' => 'Pagamento com cartão de crédito ou débito',
                'requer_comprovante' => true,
                'requer_confirmacao' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Transferência Bancária',
                'codigo' => 'TRANSF',
                'descricao' => 'Pagamento via transferência bancária',
                'requer_comprovante' => true,
                'requer_confirmacao' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Cheque',
                'codigo' => 'CHEQUE',
                'descricao' => 'Pagamento com cheque',
                'requer_comprovante' => true,
                'requer_confirmacao' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Isenção',
                'codigo' => 'ISENTO',
                'descricao' => 'Isenção de pagamento para categorias específicas',
                'requer_comprovante' => false,
                'requer_confirmacao' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de métodos de pagamento inseridos com sucesso!');
    }

    private function seedEspecialidades(): void
    {
        if (DB::table('especialidades')->count() > 0) {
            $this->command->info('Tabela de especialidades já possui dados. Pulando...');
            return;
        }

        DB::table('especialidades')->insert([
            [
                'nome' => 'Clínica Geral',
                'codigo' => 'CG',
                'descricao' => 'Medicina geral e familiar',
                'requer_encaminhamento' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Pediatria',
                'codigo' => 'PED',
                'descricao' => 'Saúde infantil',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Ginecologia',
                'codigo' => 'GIN',
                'descricao' => 'Saúde feminina',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Cardiologia',
                'codigo' => 'CAR',
                'descricao' => 'Tratamento de doenças cardiovasculares',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Dermatologia',
                'codigo' => 'DER',
                'descricao' => 'Tratamento de doenças de pele',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Oftalmologia',
                'codigo' => 'OFT',
                'descricao' => 'Tratamento de doenças oculares',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Ortopedia',
                'codigo' => 'ORT',
                'descricao' => 'Tratamento de problemas musculoesqueléticos',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Psiquiatria',
                'codigo' => 'PSI',
                'descricao' => 'Tratamento de transtornos mentais',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Neurologia',
                'codigo' => 'NEU',
                'descricao' => 'Tratamento de doenças do sistema nervoso',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Otorrinolaringologia',
                'codigo' => 'ORL',
                'descricao' => 'Tratamento de doenças de ouvido, nariz e garganta',
                'requer_encaminhamento' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de especialidades inseridos com sucesso!');
    }

    private function seedTiposConsulta(): void
    {
        if (DB::table('tipos_consulta')->count() > 0) {
            $this->command->info('Tabela de tipos de consulta já possui dados. Pulando...');
            return;
        }

        DB::table('tipos_consulta')->insert([
            [
                'nome' => 'Consulta Regular',
                'codigo' => 'REG',
                'descricao' => 'Consulta médica padrão',
                'requer_triagem' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Consulta de Especialidade',
                'codigo' => 'ESP',
                'descricao' => 'Consulta com médico especialista',
                'requer_triagem' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Consulta de Acompanhamento',
                'codigo' => 'ACOM',
                'descricao' => 'Consulta de seguimento após tratamento',
                'requer_triagem' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Emergência',
                'codigo' => 'EMG',
                'descricao' => 'Consulta de emergência',
                'requer_triagem' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Retorno com Exames',
                'codigo' => 'RET',
                'descricao' => 'Consulta para análise de resultados de exames',
                'requer_triagem' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de tipos de consulta inseridos com sucesso!');
    }

    private function seedFuncaoEspecialidades(): void
    {
        if (DB::table('funcao_especialidades')->count() > 0) {
            $this->command->info('Tabela de funções de especialidades já possui dados. Pulando...');
            return;
        }

        DB::table('funcao_especialidades')->insert([
            [
                'nome' => 'Médico Clínico Geral',
                'codigo' => 'MED-CG',
                'descricao' => 'Médico de clínica geral',
                'pode_prescrever' => true,
                'pode_solicitar_exames' => true,
                'pode_criar_prontuario' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Médico Especialista',
                'codigo' => 'MED-ESP',
                'descricao' => 'Médico especialista',
                'pode_prescrever' => true,
                'pode_solicitar_exames' => true,
                'pode_criar_prontuario' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Enfermeiro',
                'codigo' => 'ENF',
                'descricao' => 'Enfermeiro',
                'pode_prescrever' => false,
                'pode_solicitar_exames' => false,
                'pode_criar_prontuario' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Técnico de Enfermagem',
                'codigo' => 'TEC-ENF',
                'descricao' => 'Técnico de enfermagem',
                'pode_prescrever' => false,
                'pode_solicitar_exames' => false,
                'pode_criar_prontuario' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Técnico de Laboratório',
                'codigo' => 'TEC-LAB',
                'descricao' => 'Técnico de laboratório',
                'pode_prescrever' => false,
                'pode_solicitar_exames' => false,
                'pode_criar_prontuario' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Farmacêutico',
                'codigo' => 'FARM',
                'descricao' => 'Farmacêutico',
                'pode_prescrever' => false,
                'pode_solicitar_exames' => false,
                'pode_criar_prontuario' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Recepcionista',
                'codigo' => 'RECEP',
                'descricao' => 'Recepcionista',
                'pode_prescrever' => false,
                'pode_solicitar_exames' => false,
                'pode_criar_prontuario' => false,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('Dados de funções de especialidades inseridos com sucesso!');
    }

    private function seedClassificacaoRisco(): void
    {
        if (DB::table('classificacao_risco')->count() > 0) {
            $this->command->info('Tabela de classificação de risco já possui dados. Pulando...');
            return;
        }

        DB::table('classificacao_risco')->insert([
            [
                'nome' => 'Baixo Risco',
                'codigo' => 'BR',
                'descricao' => 'Pacientes com baixo risco de complicações',
                'cor' => '#52c41a',
                'nivel_prioridade' => 1,
                'ativo' => true,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Risco Moderado',
                'codigo' => 'RM',
                'descricao' => 'Pacientes com risco moderado de complicações',
                'cor' => '#faad14',
                'nivel_prioridade' => 2,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Alto Risco',
                'codigo' => 'AR',
                'descricao' => 'Pacientes com alto risco de complicações',
                'cor' => '#f5222d',
                'nivel_prioridade' => 3,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]

        ]);

        $this->command->info('Dados de classificação de risco inseridos com sucesso!');
    }

    private function seedEstadosConsulta(): void
    {
        if (DB::table('estados_consulta')->count() > 0) {
            $this->command->info('Tabela de estados de consulta já possui dados. Pulando...');
            return;
        }

        DB::table('estados_consulta')->insert([
            [
                'nome' => 'Agendada',
                'codigo' => 'AGD',
                'descricao' => 'Consulta agendada, aguardando atendimento',
                'cor' => '#1890ff',
                'icone' => 'calendar',
                'estado_final' => false,
                'requer_encerramento_ciclo' => false,
                'ordem_exibicao' => 1,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Em Andamento',
                'codigo' => 'AND',
                'descricao' => 'Consulta em andamento',
                'cor' => '#52c41a',
                'icone' => 'play-circle',
                'estado_final' => false,
                'requer_encerramento_ciclo' => false,
                'ordem_exibicao' => 2,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Aguardando Exames',
                'codigo' => 'AGX',
                'descricao' => 'Consulta em espera por resultados de exames',
                'cor' => '#faad14',
                'icone' => 'hourglass',
                'estado_final' => false,
                'requer_encerramento_ciclo' => false,
                'ordem_exibicao' => 3,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Alta',
                'codigo' => 'ALT',
                'descricao' => 'Paciente recebeu alta médica',
                'cor' => '#52c41a',
                'icone' => 'check-circle',
                'estado_final' => true,
                'requer_encerramento_ciclo' => true,
                'ordem_exibicao' => 4,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Óbito',
                'codigo' => 'OBT',
                'descricao' => 'Paciente faleceu',
                'cor' => '#f5222d',
                'icone' => 'warning',
                'estado_final' => true,
                'requer_encerramento_ciclo' => true,
                'ordem_exibicao' => 5,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Transferido',
                'codigo' => 'TRF',
                'descricao' => 'Paciente transferido para outro hospital',
                'cor' => '#722ed1',
                'icone' => 'swap',
                'estado_final' => true,
                'requer_encerramento_ciclo' => true,
                'ordem_exibicao' => 6,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Cancelado',
                'codigo' => 'CAN',
                'descricao' => 'Consulta cancelada',
                'cor' => '#d9d9d9',
                'icone' => 'close-circle',
                'estado_final' => true,
                'requer_encerramento_ciclo' => true,
                'ordem_exibicao' => 7,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
        $this->command->info('Dados de estados de consulta inseridos com sucesso!');
    }

    private function seedEstadosUrgencia(): void
    {
        if (DB::table('estados_urgencia')->count() > 0) {
            $this->command->info('Tabela de estados de urgência já possui dados. Pulando...');
            return;
        }

        DB::table('estados_urgencia')->insert([
            [
                'nome' => 'Aguardando Atendimento',
                'codigo' => 'AGD',
                'descricao' => 'Paciente aguardando atendimento na urgência',
                'cor' => '#1890ff',
                'icone' => 'hourglass',
                'nivel_prioridade' => 1,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Em Atendimento',
                'codigo' => 'EAT',
                'descricao' => 'Paciente em atendimento na urgência',
                'cor' => '#52c41a',
                'icone' => 'stethoscope',
                'nivel_prioridade' => 2,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Observação',
                'codigo' => 'OBS',
                'descricao' => 'Paciente em observação na urgência',
                'cor' => '#faad14',
                'icone' => 'eye',
                'nivel_prioridade' => 3,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Alta da Urgência',
                'codigo' => 'ALT',
                'descricao' => 'Paciente recebeu alta da urgência',
                'cor' => '#52c41a',
                'icone' => 'check-circle',
                'nivel_prioridade' => 4,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        $this->command->info('Dados de estados de urgência inseridos com sucesso!');

}

    private function seedTiposExame(): void
    {
        if (DB::table('tipos_exame')->count() > 0) {
            $this->command->info('Tabela de tipos de exames já possui dados. Pulando...');
            return;
        }

        DB::table('tipos_exame')->insert([
            [
                'nome' => 'Exame Laboratorial',
                'codigo' => 'LAB',
                'descricao' => 'Exames realizados em laboratório, como análises sanguíneas e urinárias',
                'categoria' => 'Laboratorial',
                'preco_padrao' => 150.00,
                'tempo_estimado_minutos' => 60,
                'requer_jejum' => false,
                'instrucoes_preparo' => 'Nenhuma preparação especial necessária',
                'instrucoes_coleta' => 'Coleta realizada no laboratório',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Exame de Imagem',
                'codigo' => 'IMG',
                'descricao' => 'Exames de diagnóstico por imagem, como raios-X, ultrassonografias e ressonâncias magnéticas',
                'categoria' => 'Imagem',
                'preco_padrao' => 300.00,
                'tempo_estimado_minutos' => 90,
                'requer_jejum' => false,
                'instrucoes_preparo' => 'Nenhuma preparação especial necessária',
                'instrucoes_coleta' => 'Realizado na sala de exames de imagem',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nome' => 'Exame Cardiológico',
                'codigo' => 'CARD',
                'descricao' => 'Exames relacionados ao sistema cardiovascular, como eletrocardiogramas e ecocardiogramas',
                'categoria' => 'Cardiológico',
                'preco_padrao' => 250.00,
                'tempo_estimado_minutos' => 75,
                'requer_jejum' => false,
                'instrucoes_preparo' => 'Nenhuma preparação especial necessária',
                'instrucoes_coleta' => 'Realizado na sala de cardiologia',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        $this->command->info('Dados de tipos de exames inseridos com sucesso!');


}
}
