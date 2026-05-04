<?php

namespace Database\Seeders;

use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PermissaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissoes = [
            // Permissões para usuários
            [
                'nome' => 'usuarios.listar',
                'codigo' => 'usr_list',
                'descricao' => 'Listar usuários',
            ],
            [
                'nome' => 'usuarios.visualizar',
                'codigo' => 'usr_view',
                'descricao' => 'Visualizar detalhes de usuários',
            ],
            [
                'nome' => 'usuarios.criar',
                'codigo' => 'usr_create',
                'descricao' => 'Criar novos usuários',
            ],
            [
                'nome' => 'usuarios.editar',
                'codigo' => 'usr_edit',
                'descricao' => 'Editar usuários existentes',
            ],
            [
                'nome' => 'usuarios.excluir',
                'codigo' => 'usr_delete',
                'descricao' => 'Excluir usuários',
            ],

            // Permissões para perfis
            [
                'nome' => 'perfis.listar',
                'codigo' => 'role_list',
                'descricao' => 'Listar perfis',
            ],
            [
                'nome' => 'perfis.visualizar',
                'codigo' => 'role_view',
                'descricao' => 'Visualizar detalhes de perfis',
            ],
            [
                'nome' => 'perfis.criar',
                'codigo' => 'role_create',
                'descricao' => 'Criar novos perfis',
            ],
            [
                'nome' => 'perfis.editar',
                'codigo' => 'role_edit',
                'descricao' => 'Editar perfis existentes',
            ],
            [
                'nome' => 'perfis.excluir',
                'codigo' => 'role_delete',
                'descricao' => 'Excluir perfis',
            ],

            // Permissões para pacientes
            [
                'nome' => 'pacientes.listar',
                'codigo' => 'pat_list',
                'descricao' => 'Listar pacientes',
            ],
            [
                'nome' => 'pacientes.visualizar',
                'codigo' => 'pat_view',
                'descricao' => 'Visualizar detalhes de pacientes',
            ],
            [
                'nome' => 'pacientes.criar',
                'codigo' => 'pat_create',
                'descricao' => 'Criar novos pacientes',
            ],
            [
                'nome' => 'pacientes.editar',
                'codigo' => 'pat_edit',
                'descricao' => 'Editar pacientes existentes',
            ],
            [
                'nome' => 'pacientes.excluir',
                'codigo' => 'pat_delete',
                'descricao' => 'Excluir pacientes',
            ],

            // Permissões para consultas
            [
                'nome' => 'consultas.listar',
                'codigo' => 'cons_list',
                'descricao' => 'Listar consultas',
            ],
            [
                'nome' => 'consultas.visualizar',
                'codigo' => 'cons_view',
                'descricao' => 'Visualizar detalhes de consultas',
            ],
            [
                'nome' => 'consultas.criar',
                'codigo' => 'cons_create',
                'descricao' => 'Criar novas consultas',
            ],
            [
                'nome' => 'consultas.editar',
                'codigo' => 'cons_edit',
                'descricao' => 'Editar consultas existentes',
            ],
            [
                'nome' => 'consultas.excluir',
                'codigo' => 'cons_delete',
                'descricao' => 'Excluir consultas',
            ],

            // Permissões para triagem
            [
                'nome' => 'triagem.listar',
                'codigo' => 'tri_list',
                'descricao' => 'Listar triagens',
            ],
            [
                'nome' => 'triagem.visualizar',
                'codigo' => 'tri_view',
                'descricao' => 'Visualizar detalhes de triagens',
            ],
            [
                'nome' => 'triagem.criar',
                'codigo' => 'tri_create',
                'descricao' => 'Criar novas triagens',
            ],
            [
                'nome' => 'triagem.editar',
                'codigo' => 'tri_edit',
                'descricao' => 'Editar triagens existentes',
            ],

            // Permissões para exames
            [
                'nome' => 'exames.listar',
                'codigo' => 'exa_list',
                'descricao' => 'Listar exames',
            ],
            [
                'nome' => 'exames.visualizar',
                'codigo' => 'exa_view',
                'descricao' => 'Visualizar detalhes de exames',
            ],
            [
                'nome' => 'exames.criar',
                'codigo' => 'exa_create',
                'descricao' => 'Criar novos exames',
            ],
            [
                'nome' => 'exames.editar',
                'codigo' => 'exa_edit',
                'descricao' => 'Editar exames existentes',
            ],
            [
                'nome' => 'exames.excluir',
                'codigo' => 'exa_delete',
                'descricao' => 'Excluir exames',
            ],

            // Permissões para prescrições
            [
                'nome' => 'prescricoes.listar',
                'codigo' => 'pres_list',
                'descricao' => 'Listar prescrições',
            ],
            [
                'nome' => 'prescricoes.visualizar',
                'codigo' => 'pres_view',
                'descricao' => 'Visualizar detalhes de prescrições',
            ],
            [
                'nome' => 'prescricoes.criar',
                'codigo' => 'pres_create',
                'descricao' => 'Criar novas prescrições',
            ],
            [
                'nome' => 'prescricoes.editar',
                'codigo' => 'pres_edit',
                'descricao' => 'Editar prescrições existentes',
            ],
            [
                'nome' => 'prescricoes.excluir',
                'codigo' => 'pres_delete',
                'descricao' => 'Excluir prescrições',
            ],
        ];

        foreach ($permissoes as $permissao) {
            Permissao::create($permissao);
        }
    }
}