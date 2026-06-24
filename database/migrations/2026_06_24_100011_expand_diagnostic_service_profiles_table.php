<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_service_profiles', function (Blueprint $table) {
            $table->string('default_specimen_type')->nullable()->after('loinc_display');
            $table->text('preparation_instructions')->nullable()->after('default_specimen_type');
            $table->boolean('auto_verify_eligible')->default(false)->after('preparation_instructions');
            $table->unsignedInteger('turnaround_time_minutes')->nullable()->after('auto_verify_eligible');
            $table->string('modality')->nullable()->after('turnaround_time_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_service_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'default_specimen_type',
                'preparation_instructions',
                'auto_verify_eligible',
                'turnaround_time_minutes',
                'modality',
            ]);
        });
    }
};
