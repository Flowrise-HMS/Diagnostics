<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_result_files', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->string('checksum')->nullable()->after('file_size');
            $table->boolean('is_authoritative')->default(false)->after('checksum');
            $table->text('notes')->nullable()->after('is_authoritative');

            $table->index(['is_authoritative']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_result_files', function (Blueprint $table) {
            $table->dropIndex(['is_authoritative']);
            $table->dropColumn(['file_size', 'checksum', 'is_authoritative', 'notes']);
        });
    }
};
