<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_report_versions', function (Blueprint $table) {
            $table->string('report_number', 30)->nullable()->after('fulfillment_id');
            $table->string('title')->nullable()->after('version');
            $table->text('conclusion')->nullable()->after('status');
            $table->json('conclusion_codes')->nullable()->after('conclusion');
            $table->foreignId('performed_by')->nullable()->after('conclusion_codes')->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->after('performed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->boolean('is_critical')->default(false)->after('verified_at');
            $table->timestamp('critical_notified_at')->nullable()->after('is_critical');
            $table->json('metadata')->nullable()->after('critical_notified_at');

            $table->index(['report_number']);
            $table->index(['is_critical']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_report_versions', function (Blueprint $table) {
            $table->dropIndex(['report_number']);
            $table->dropIndex(['is_critical']);
            $table->dropConstrainedForeignId('performed_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'report_number',
                'title',
                'conclusion',
                'conclusion_codes',
                'verified_at',
                'is_critical',
                'critical_notified_at',
                'metadata',
            ]);
        });
    }
};
