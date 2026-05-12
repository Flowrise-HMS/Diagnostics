<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_specimens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('fulfillment_id')
                ->constrained('diagnostic_fulfillments')
                ->cascadeOnDelete();
            $table->string('specimen_type');
            $table->string('status')->default('collected');
            $table->timestamps();

            $table->index(['fulfillment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_specimens');
    }
};
