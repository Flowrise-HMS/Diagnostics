<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('fulfillment_id')
                ->constrained('diagnostic_fulfillments')
                ->cascadeOnDelete();
            $table->foreignUuid('specimen_id')
                ->nullable()
                ->constrained('diagnostic_specimens')
                ->nullOnDelete();
            $table->string('code');
            $table->string('status')->default('registered');
            $table->timestamps();

            $table->index(['fulfillment_id', 'status']);
            $table->index('specimen_id');
            $table->index('branch_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_observations');
    }
};
