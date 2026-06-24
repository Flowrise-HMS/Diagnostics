<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_reference_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')
                ->constrained('diagnostic_service_profiles')
                ->cascadeOnDelete();
            $table->string('gender')->default('any');
            $table->unsignedInteger('age_min_months')->nullable();
            $table->unsignedInteger('age_max_months')->nullable();
            $table->decimal('min_value', 20, 6)->nullable();
            $table->decimal('max_value', 20, 6)->nullable();
            $table->text('range_text')->nullable();
            $table->string('units')->nullable();
            $table->decimal('critical_low', 20, 6)->nullable();
            $table->decimal('critical_high', 20, 6)->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'gender']);
            $table->index(['profile_id', 'age_min_months', 'age_max_months'], 'diag_ref_ranges_profile_age_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_reference_ranges');
    }
};
