<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_result_template_fields', function (Blueprint $table) {
            $table->string('observation_code')->nullable()->after('template_id');
            $table->string('observation_name')->nullable()->after('observation_code');
            $table->string('data_type')->nullable()->after('observation_name');
            $table->string('default_units')->nullable()->after('value_type');
            $table->boolean('is_required')->default(false)->after('default_units');
            $table->decimal('reference_range_low', 20, 6)->nullable()->after('is_required');
            $table->decimal('reference_range_high', 20, 6)->nullable()->after('reference_range_low');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_result_template_fields', function (Blueprint $table) {
            $table->dropColumn([
                'observation_code',
                'observation_name',
                'data_type',
                'default_units',
                'is_required',
                'reference_range_low',
                'reference_range_high',
            ]);
        });
    }
};
