<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_studies', function (Blueprint $table) {
            $table->string('uid')->nullable()->after('fulfillment_id');
            $table->string('accession_number')->nullable()->after('uid');
            $table->string('modality')->nullable()->after('accession_number');
            $table->string('body_site')->nullable()->after('modality');
            $table->timestamp('performed_at')->nullable()->after('body_site');
            $table->foreignId('performed_by')->nullable()->after('performed_at')->constrained('users')->nullOnDelete();
            $table->foreignId('interpreter_id')->nullable()->after('performed_by')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('number_of_series')->default(0)->after('interpreter_id');
            $table->text('conclusion')->nullable()->after('number_of_series');
            $table->json('metadata')->nullable()->after('status');

            $table->unique('fulfillment_id');
            $table->index(['uid']);
            $table->index(['accession_number']);
            $table->index(['modality']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_studies', function (Blueprint $table) {
            $table->dropUnique(['fulfillment_id']);
            $table->dropIndex(['uid']);
            $table->dropIndex(['accession_number']);
            $table->dropIndex(['modality']);
            $table->dropConstrainedForeignId('performed_by');
            $table->dropConstrainedForeignId('interpreter_id');
            $table->dropColumn([
                'uid',
                'accession_number',
                'modality',
                'body_site',
                'performed_at',
                'number_of_series',
                'conclusion',
                'metadata',
            ]);
        });
    }
};
