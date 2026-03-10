<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Paciente;
use App\Models\UtenteAutonomo;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NidGenerationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function paciente_gera_nid_automaticamente()
    {
        $paciente = Paciente::create([
            'nome' => 'João',
            'apelido' => 'Silva',
            'data_nascimento' => '1990-05-15',
            'genero' => 'masculino',
            'celular' => '84123456789',
        ]);

        $ano = now()->year;
        $this->assertEquals("0001/{$ano}", $paciente->nid);
        $this->assertEquals($paciente->nid, $paciente->codigo_paciente);
    }

    /** @test */
    public function paciente_gera_nids_sequenciais()
    {
        $ano = now()->year;

        $paciente1 = Paciente::create([
            'nome' => 'João',
            'apelido' => 'Silva',
            'data_nascimento' => '1990-05-15',
            'genero' => 'masculino',
            'celular' => '84123456781',
        ]);

        $paciente2 = Paciente::create([
            'nome' => 'Maria',
            'apelido' => 'Santos',
            'data_nascimento' => '1992-08-20',
            'genero' => 'feminino',
            'celular' => '84123456782',
        ]);

        $paciente3 = Paciente::create([
            'nome' => 'Carlos',
            'apelido' => 'Moura',
            'data_nascimento' => '1988-03-10',
            'genero' => 'masculino',
            'celular' => '84123456783',
        ]);

        $this->assertEquals("0001/{$ano}", $paciente1->nid);
        $this->assertEquals("0002/{$ano}", $paciente2->nid);
        $this->assertEquals("0003/{$ano}", $paciente3->nid);
    }

    /** @test */
    public function paciente_aceita_nid_manual()
    {
        $paciente = Paciente::create([
            'nid' => '1234/2025',
            'nome' => 'Ana',
            'apelido' => 'Costa',
            'data_nascimento' => '1995-12-01',
            'genero' => 'feminino',
            'celular' => '84123456789',
        ]);

        $this->assertEquals('1234/2025', $paciente->nid);
    }

    /** @test */
    public function utente_autonomo_gera_nid_automaticamente()
    {
        $utente = UtenteAutonomo::create([
            'nome' => 'Pedro',
            'apelido' => 'Oliveira',
            'celular' => '84987654321',
        ]);

        $ano = now()->year;
        $this->assertEquals("UT001/{$ano}", $utente->nid);
        $this->assertEquals($utente->nid, $utente->codigo_utente);
    }

    /** @test */
    public function utente_autonomo_gera_nids_sequenciais()
    {
        $ano = now()->year;

        $utente1 = UtenteAutonomo::create([
            'nome' => 'Pedro',
            'apelido' => 'Oliveira',
            'celular' => '84987654321',
        ]);

        $utente2 = UtenteAutonomo::create([
            'nome' => 'Laura',
            'apelido' => 'Ferreira',
            'celular' => '84987654322',
        ]);

        $utente3 = UtenteAutonomo::create([
            'nome' => 'Ricardo',
            'apelido' => 'Almeida',
            'celular' => '84987654323',
        ]);

        $this->assertEquals("UT001/{$ano}", $utente1->nid);
        $this->assertEquals("UT002/{$ano}", $utente2->nid);
        $this->assertEquals("UT003/{$ano}", $utente3->nid);
    }

    /** @test */
    public function utente_autonomo_aceita_nid_manual()
    {
        $utente = UtenteAutonomo::create([
            'nid' => 'UT500/2025',
            'nome' => 'Beatriz',
            'apelido' => 'Lima',
            'celular' => '84987654321',
        ]);

        $this->assertEquals('UT500/2025', $utente->nid);
    }

    /** @test */
    public function pode_consultar_proximo_nid_paciente()
    {
        $ano = now()->year;
        
        // Primeiro NID
        $proximoNid = Paciente::proximoNID();
        $this->assertEquals("0001/{$ano}", $proximoNid);

        // Criar um paciente
        Paciente::create([
            'nome' => 'Teste',
            'apelido' => 'Teste',
            'data_nascimento' => '1990-01-01',
            'genero' => 'masculino',
            'celular' => '84123456789',
        ]);

        // Próximo NID deve ser 0002
        $proximoNid = Paciente::proximoNID();
        $this->assertEquals("0002/{$ano}", $proximoNid);
    }

    /** @test */
    public function pode_consultar_proximo_nid_utente_autonomo()
    {
        $ano = now()->year;
        
        // Primeiro NID
        $proximoNid = UtenteAutonomo::proximoNID();
        $this->assertEquals("UT001/{$ano}", $proximoNid);

        // Criar um utente
        UtenteAutonomo::create([
            'nome' => 'Teste',
            'apelido' => 'Teste',
            'celular' => '84987654321',
        ]);

        // Próximo NID deve ser UT002
        $proximoNid = UtenteAutonomo::proximoNID();
        $this->assertEquals("UT002/{$ano}", $proximoNid);
    }

    /** @test */
    public function nid_formata_com_zeros_a_esquerda()
    {
        $ano = now()->year;

        // Criar 9 pacientes
        for ($i = 1; $i <= 9; $i++) {
            Paciente::create([
                'nome' => "Paciente {$i}",
                'apelido' => 'Teste',
                'data_nascimento' => '1990-01-01',
                'genero' => 'masculino',
                'celular' => "8412345678{$i}",
            ]);
        }

        // Criar o 10º paciente
        $paciente10 = Paciente::create([
            'nome' => 'Paciente 10',
            'apelido' => 'Teste',
            'data_nascimento' => '1990-01-01',
            'genero' => 'masculino',
            'celular' => '84123456710',
        ]);

        $this->assertEquals("0010/{$ano}", $paciente10->nid);
    }
}
