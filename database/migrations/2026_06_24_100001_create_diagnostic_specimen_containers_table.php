<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_specimen_containers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('specimen_id')
                ->constrained('diagnostic_specimens')
                ->cascadeOnDelete();
            $table->string('container_type');
            $table->string('additive')->nullable();
            $table->decimal('capacity', 10, 2)->nullable();
            $table->string('capacity_unit')->nullable();
            $table->string('identifier')->nullable();
            $table->timestamps();

            $table->index(['specimen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_specimen_containers');
    }
};
