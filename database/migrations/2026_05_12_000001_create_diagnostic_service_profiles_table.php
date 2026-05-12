<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_service_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')
                ->unique()
                ->constrained('services')
                ->cascadeOnDelete();
            $table->string('discipline');
            $table->string('loinc_code')->nullable();
            $table->string('loinc_display')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['discipline', 'is_active']);
            $table->index('loinc_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_service_profiles');
    }
};
