<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DiagnosticStudy is branch-scoped through BaseModel, but the table never had the
 * column the scope filters on, so any request with a current branch failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_studies', function (Blueprint $table) {
            $table->foreignUuid('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_studies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
