<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_result_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('fulfillment_id')
                ->nullable()
                ->constrained('diagnostic_fulfillments')
                ->cascadeOnDelete();
            $table->foreignUuid('report_version_id')
                ->nullable()
                ->constrained('diagnostic_report_versions')
                ->nullOnDelete();
            $table->string('file_type')->nullable();
            $table->string('source')->nullable();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['fulfillment_id', 'source']);
            $table->index('report_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_result_files');
    }
};
