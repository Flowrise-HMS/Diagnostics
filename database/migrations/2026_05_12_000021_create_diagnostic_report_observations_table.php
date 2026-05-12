<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_report_observations', function (Blueprint $table) {
            $table->foreignUuid('report_version_id')
                ->constrained('diagnostic_report_versions')
                ->cascadeOnDelete();
            $table->foreignUuid('observation_id')
                ->constrained('diagnostic_observations')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['report_version_id', 'observation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_report_observations');
    }
};
