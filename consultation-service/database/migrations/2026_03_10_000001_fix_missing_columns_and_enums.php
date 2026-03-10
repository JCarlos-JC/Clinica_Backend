<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Adicionar 'finalizada_com_exames' ao ENUM de status da tabela consultas
        DB::statement("ALTER TABLE consultas MODIFY COLUMN status ENUM(
            'agendada',
            'em_atendimento',
            'finalizada',
            'finalizada_com_exames',
            'cancelada',
            'nao_compareceu',
            'remarcada',
            'transferido_especialidade',
            'aguardando_exames',
            'retorno_exames'
        ) NOT NULL DEFAULT 'agendada'");

        // 2. Adicionar coluna data_realizacao à tabela exames (se não existir)
        if (!Schema::hasColumn('exames', 'data_realizacao')) {
            Schema::table('exames', function (Blueprint $table) {
                $table->dateTime('data_realizacao')->nullable()->after('data_hora_coleta');
            });
        }

        // 3. Adicionar colunas resultado e resultado_texto à tabela exames (se não existir)
        if (!Schema::hasColumn('exames', 'resultado')) {
            Schema::table('exames', function (Blueprint $table) {
                $table->text('resultado')->nullable()->after('data_realizacao');
            });
        }

        // 4. Adicionar colunas cor e icone à tabela especialidades (se existir e não tiver as colunas)
        if (Schema::hasTable('especialidades')) {
            if (!Schema::hasColumn('especialidades', 'cor')) {
                Schema::table('especialidades', function (Blueprint $table) {
                    $table->string('cor', 20)->nullable()->after('descricao');
                });
            }

            if (!Schema::hasColumn('especialidades', 'icone')) {
                Schema::table('especialidades', function (Blueprint $table) {
                    $table->string('icone', 100)->nullable()->after('cor');
                });
            }

            if (!Schema::hasColumn('especialidades', 'requer_encaminhamento')) {
                Schema::table('especialidades', function (Blueprint $table) {
                    $table->boolean('requer_encaminhamento')->default(false)->after('icone');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter ENUM para sem finalizada_com_exames
        DB::statement("ALTER TABLE consultas MODIFY COLUMN status ENUM(
            'agendada',
            'em_atendimento',
            'finalizada',
            'cancelada',
            'nao_compareceu',
            'remarcada',
            'transferido_especialidade',
            'aguardando_exames',
            'retorno_exames'
        ) NOT NULL DEFAULT 'agendada'");

        // Remover colunas adicionadas
        if (Schema::hasColumn('exames', 'data_realizacao')) {
            Schema::table('exames', function (Blueprint $table) {
                $table->dropColumn('data_realizacao');
            });
        }

        if (Schema::hasColumn('exames', 'resultado')) {
            Schema::table('exames', function (Blueprint $table) {
                $table->dropColumn('resultado');
            });
        }

        if (Schema::hasTable('especialidades')) {
            foreach (['cor', 'icone', 'requer_encaminhamento'] as $col) {
                if (Schema::hasColumn('especialidades', $col)) {
                    Schema::table('especialidades', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        }
    }
};
