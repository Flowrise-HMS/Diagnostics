<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_specimens', function (Blueprint $table) {
            $table->foreignUuid('parent_specimen_id')->nullable()->after('fulfillment_id')->constrained('diagnostic_specimens')->nullOnDelete();
            $table->string('accession_number', 30)->nullable()->after('parent_specimen_id');
            $table->string('specimen_class')->nullable()->after('specimen_type');
            $table->string('collection_method')->nullable()->after('specimen_class');
            $table->string('body_site')->nullable()->after('collection_method');
            $table->unsignedInteger('fasting_hours')->nullable()->after('body_site');
            $table->decimal('volume', 10, 2)->nullable()->after('fasting_hours');
            $table->string('volume_unit')->nullable()->after('volume');
            $table->string('container_type')->nullable()->after('volume_unit');
            $table->string('container_id')->nullable()->after('container_type');
            $table->string('barcode', 100)->nullable()->after('container_id');
            $table->timestamp('collected_at')->nullable()->after('barcode');
            $table->foreignId('collected_by')->nullable()->after('collected_at')->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable()->after('collected_by');
            $table->string('condition')->default('acceptable')->after('received_at');
            $table->text('condition_note')->nullable()->after('condition');
            $table->string('storage_location')->nullable()->after('condition_note');
            $table->softDeletes();

            $table->index(['accession_number']);
            $table->index(['barcode']);
            $table->index(['specimen_type']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_specimens', function (Blueprint $table) {
            $table->dropIndex(['accession_number']);
            $table->dropIndex(['barcode']);
            $table->dropIndex(['specimen_type']);
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('parent_specimen_id');
            $table->dropConstrainedForeignId('collected_by');
            $table->dropColumn([
                'accession_number',
                'specimen_class',
                'collection_method',
                'body_site',
                'fasting_hours',
                'volume',
                'volume_unit',
                'container_type',
                'container_id',
                'barcode',
                'collected_at',
                'received_at',
                'condition',
                'condition_note',
                'storage_location',
            ]);
        });
    }
};
