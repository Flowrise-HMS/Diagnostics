<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference ranges belong to one analyte. Scoping them to a result field stops a
 * single profile-wide range being stamped on every field of a multi-field test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_reference_ranges', function (Blueprint $table) {
            $table->foreignUuid('template_field_id')
                ->nullable()
                ->after('profile_id')
                ->constrained('diagnostic_result_template_fields')
                ->cascadeOnDelete();
            $table->index(['template_field_id', 'gender'], 'diag_ref_ranges_field_gender_idx');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_reference_ranges', function (Blueprint $table) {
            $table->dropIndex('diag_ref_ranges_field_gender_idx');
            $table->dropConstrainedForeignId('template_field_id');
        });
    }
};
