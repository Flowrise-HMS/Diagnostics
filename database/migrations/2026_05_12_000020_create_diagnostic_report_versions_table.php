<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_report_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('fulfillment_id')
                ->nullable()
                ->constrained('diagnostic_fulfillments')
                ->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('preliminary');
            $table->timestamps();

            $table->unique(['fulfillment_id', 'version']);
            $table->index(['fulfillment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_report_versions');
    }
};
