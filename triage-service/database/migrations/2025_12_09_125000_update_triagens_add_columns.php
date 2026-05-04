<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('triagens', function (Blueprint $table) {
            // Basic identifiers
            if (! Schema::hasColumn('triagens', 'codigo_triagem')) {
                $table->string('codigo_triagem', 50)->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('triagens', 'paciente_id')) {
                $table->unsignedBigInteger('paciente_id')->nullable()->index()->after('codigo_triagem');
            }

            if (! Schema::hasColumn('triagens', 'triagem_id')) {
                $table->unsignedBigInteger('triagem_id')->nullable()->index()->after('paciente_id');
            }

            // Patient fields
            if (! Schema::hasColumn('triagens', 'nid')) {
                $table->string('nid')->nullable()->after('triagem_id');
            }

            if (! Schema::hasColumn('triagens', 'nome')) {
                $table->string('nome')->nullable()->after('nid');
            }

            if (! Schema::hasColumn('triagens', 'apelido')) {
                $table->string('apelido')->nullable()->after('nome');
            }

            if (! Schema::hasColumn('triagens', 'genero')) {
                $table->string('genero')->nullable()->after('apelido');
            }

            if (! Schema::hasColumn('triagens', 'data_nascimento')) {
                $table->date('data_nascimento')->nullable()->after('genero');
            }

            // Triage meta
            if (! Schema::hasColumn('triagens', 'enfermeiro_id')) {
                $table->unsignedBigInteger('enfermeiro_id')->nullable()->after('data_nascimento');
            }

            if (! Schema::hasColumn('triagens', 'enfermeiro_nome')) {
                $table->string('enfermeiro_nome')->nullable()->after('enfermeiro_id');
            }

            if (! Schema::hasColumn('triagens', 'data_hora_inicio')) {
                $table->dateTime('data_hora_inicio')->nullable()->after('enfermeiro_nome');
            }

            if (! Schema::hasColumn('triagens', 'data_hora_fim')) {
                $table->dateTime('data_hora_fim')->nullable()->after('data_hora_inicio');
            }

            if (! Schema::hasColumn('triagens', 'data_cadastro')) {
                $table->dateTime('data_cadastro')->nullable()->after('data_hora_fim');
            }

            if (! Schema::hasColumn('triagens', 'data_triagem')) {
                $table->dateTime('data_triagem')->nullable()->after('data_cadastro');
            }

            if (! Schema::hasColumn('triagens', 'estado_urgencia')) {
                $table->string('estado_urgencia')->nullable()->after('data_triagem');
            }

            if (! Schema::hasColumn('triagens', 'tipo_utente')) {
                $table->string('tipo_utente')->nullable()->after('estado_urgencia');
            }

            if (! Schema::hasColumn('triagens', 'tipo_triagem')) {
                $table->string('tipo_triagem')->nullable()->after('tipo_utente');
            }

            if (! Schema::hasColumn('triagens', 'status')) {
                $table->string('status')->default('triagem_concluida')->after('tipo_triagem');
            }

            if (! Schema::hasColumn('triagens', 'consulta_id')) {
                $table->unsignedBigInteger('consulta_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('triagens', 'consulta_agendada')) {
                $table->boolean('consulta_agendada')->default(false)->after('consulta_id');
            }

            if (! Schema::hasColumn('triagens', 'observacoes')) {
                $table->text('observacoes')->nullable()->after('consulta_agendada');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('triagens', function (Blueprint $table) {
            $cols = [
                'codigo_triagem','paciente_id','triagem_id','nid','nome','apelido','genero','data_nascimento',
                'enfermeiro_id','enfermeiro_nome','data_hora_inicio','data_hora_fim','data_cadastro','data_triagem',
                'estado_urgencia','tipo_utente','tipo_triagem','status','consulta_id','consulta_agendada','observacoes'
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('triagens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
