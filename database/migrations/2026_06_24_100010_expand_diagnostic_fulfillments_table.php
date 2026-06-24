<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_fulfillments', function (Blueprint $table) {
            $table->string('accession_number', 30)->nullable()->after('discipline');
            $table->string('priority')->default('routine')->after('status');
            $table->text('clinical_indication')->nullable()->after('priority');
            $table->json('diagnosis_codes')->nullable()->after('clinical_indication');
            $table->timestamp('scheduled_at')->nullable()->after('diagnosis_codes');
            $table->timestamp('collection_date')->nullable()->after('scheduled_at');
            $table->timestamp('cancelled_at')->nullable()->after('collection_date');
            $table->text('cancelled_reason')->nullable()->after('cancelled_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_reason')->constrained('users')->nullOnDelete();
            $table->foreignId('performer_id')->nullable()->after('cancelled_by')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('performer_id');
            $table->json('metadata')->nullable()->after('notes');
            $table->softDeletes();

            $table->unique(['branch_id', 'accession_number']);
            $table->index(['priority']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_fulfillments', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'accession_number']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['created_at']);
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropConstrainedForeignId('performer_id');
            $table->dropColumn([
                'accession_number',
                'priority',
                'clinical_indication',
                'diagnosis_codes',
                'scheduled_at',
                'collection_date',
                'cancelled_at',
                'cancelled_reason',
                'notes',
                'metadata',
            ]);
        });
    }
};
