<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_fulfillment_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fulfillment_id')
                ->constrained('diagnostic_fulfillments')
                ->cascadeOnDelete();
            $table->string('resource_type');
            $table->string('resource_id');
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fulfillment_id']);
            $table->index(['resource_type', 'resource_id'], 'diag_fulfill_alloc_resource_idx');
            $table->index(['scheduled_start', 'scheduled_end'], 'diag_fulfill_alloc_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_fulfillment_allocations');
    }
};
