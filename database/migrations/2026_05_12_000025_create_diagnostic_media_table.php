<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('study_id')
                ->constrained('diagnostic_studies')
                ->cascadeOnDelete();
            $table->string('file_type')->nullable();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['study_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_media');
    }
};
