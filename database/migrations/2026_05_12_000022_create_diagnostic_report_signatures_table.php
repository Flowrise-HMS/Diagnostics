<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_report_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('report_version_id')
                ->constrained('diagnostic_report_versions')
                ->cascadeOnDelete();
            $table->foreignId('signed_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamp('signed_at')->useCurrent();
            $table->longText('notes')->nullable();
            $table->timestamps();

            $table->index(['report_version_id', 'signed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_report_signatures');
    }
};
