<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_observation_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('observation_id')
                ->constrained('diagnostic_observations')
                ->cascadeOnDelete();
            $table->string('code');
            $table->string('display')->nullable();
            $table->string('value_type')->nullable();
            $table->decimal('value_numeric', 20, 6)->nullable();
            $table->text('value_text')->nullable();
            $table->string('value_coded')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->decimal('value_range_low', 20, 6)->nullable();
            $table->decimal('value_range_high', 20, 6)->nullable();
            $table->decimal('value_quantity_value', 20, 6)->nullable();
            $table->string('value_quantity_unit')->nullable();
            $table->string('data_absent_reason')->nullable();
            $table->string('units')->nullable();
            $table->decimal('reference_range_min', 20, 6)->nullable();
            $table->decimal('reference_range_max', 20, 6)->nullable();
            $table->text('reference_range_text')->nullable();
            $table->string('abnormal_flag')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['observation_id', 'code'], 'diag_obs_comp_obs_code_idx');
            $table->index(['observation_id', 'sort_order'], 'diag_obs_comp_obs_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_observation_components');
    }
};
