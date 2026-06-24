<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_specimen_processing_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('specimen_id')
                ->constrained('diagnostic_specimens')
                ->cascadeOnDelete();
            $table->string('procedure');
            $table->string('additive')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['specimen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_specimen_processing_events');
    }
};
