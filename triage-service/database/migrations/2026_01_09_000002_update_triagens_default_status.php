<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the default value for status column
        DB::statement("ALTER TABLE triagens MODIFY COLUMN status VARCHAR(255) DEFAULT 'triagem_concluida'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original default
        DB::statement("ALTER TABLE triagens MODIFY COLUMN status VARCHAR(255) DEFAULT 'aguardando_triagem'");
    }
};
