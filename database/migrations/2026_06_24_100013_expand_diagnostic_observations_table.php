<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_observations', function (Blueprint $table) {
            $table->foreignUuid('profile_id')->nullable()->after('fulfillment_id')->constrained('diagnostic_service_profiles')->nullOnDelete();
            $table->foreignUuid('parent_observation_id')->nullable()->after('profile_id')->constrained('diagnostic_observations')->nullOnDelete();
            $table->string('display')->nullable()->after('code');
            $table->string('value_type')->nullable()->after('status');
            $table->decimal('value_numeric', 20, 6)->nullable()->after('value_type');
            $table->text('value_text')->nullable()->after('value_numeric');
            $table->string('value_coded')->nullable()->after('value_text');
            $table->boolean('value_boolean')->nullable()->after('value_coded');
            $table->decimal('value_range_low', 20, 6)->nullable()->after('value_boolean');
            $table->decimal('value_range_high', 20, 6)->nullable()->after('value_range_low');
            $table->decimal('value_quantity_value', 20, 6)->nullable()->after('value_range_high');
            $table->string('value_quantity_unit')->nullable()->after('value_quantity_value');
            $table->string('data_absent_reason')->nullable()->after('value_quantity_unit');
            $table->string('units')->nullable()->after('data_absent_reason');
            $table->decimal('reference_range_min', 20, 6)->nullable()->after('units');
            $table->decimal('reference_range_max', 20, 6)->nullable()->after('reference_range_min');
            $table->text('reference_range_text')->nullable()->after('reference_range_max');
            $table->string('abnormal_flag')->nullable()->after('reference_range_text');
            $table->string('interpretation')->nullable()->after('abnormal_flag');
            $table->foreignId('performed_by')->nullable()->after('interpretation')->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable()->after('performed_by');
            $table->foreignId('verified_by')->nullable()->after('performed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('notes')->nullable()->after('verified_at');
            $table->unsignedInteger('sort_order')->default(0)->after('notes');
            $table->softDeletes();

            $table->index(['abnormal_flag']);
            $table->index(['parent_observation_id']);
            $table->index(['performed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_observations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_id');
            $table->dropConstrainedForeignId('parent_observation_id');
            $table->dropConstrainedForeignId('performed_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropIndex(['abnormal_flag']);
            $table->dropIndex(['performed_at']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'display',
                'value_type',
                'value_numeric',
                'value_text',
                'value_coded',
                'value_boolean',
                'value_range_low',
                'value_range_high',
                'value_quantity_value',
                'value_quantity_unit',
                'data_absent_reason',
                'units',
                'reference_range_min',
                'reference_range_max',
                'reference_range_text',
                'abnormal_flag',
                'interpretation',
                'performed_at',
                'verified_at',
                'notes',
                'sort_order',
            ]);
        });
    }
};
