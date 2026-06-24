<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_panel_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('panel_id')
                ->constrained('diagnostic_panels')
                ->cascadeOnDelete();
            $table->foreignUuid('child_profile_id')
                ->constrained('diagnostic_service_profiles')
                ->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['panel_id', 'child_profile_id']);
            $table->index(['panel_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_panel_items');
    }
};
