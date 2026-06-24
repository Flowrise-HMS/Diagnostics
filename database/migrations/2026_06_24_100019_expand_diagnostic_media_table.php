<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_media', function (Blueprint $table) {
            $table->string('uid')->nullable()->after('study_id');
            $table->string('series_uid')->nullable()->after('uid');
            $table->unsignedInteger('series_number')->nullable()->after('series_uid');
            $table->unsignedInteger('instance_number')->nullable()->after('series_number');
            $table->string('sop_class')->nullable()->after('instance_number');
            $table->string('modality')->nullable()->after('sop_class');
            $table->string('mime_type')->nullable()->after('file_path');
            $table->string('thumbnail_path')->nullable()->after('mime_type');
            $table->string('viewer_url')->nullable()->after('thumbnail_path');
            $table->boolean('is_key_image')->default(false)->after('viewer_url');

            $table->index(['study_id', 'series_uid']);
            $table->index(['study_id', 'is_key_image']);
            $table->index(['uid']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_media', function (Blueprint $table) {
            $table->dropIndex(['study_id', 'series_uid']);
            $table->dropIndex(['study_id', 'is_key_image']);
            $table->dropIndex(['uid']);
            $table->dropColumn([
                'uid',
                'series_uid',
                'series_number',
                'instance_number',
                'sop_class',
                'modality',
                'mime_type',
                'thumbnail_path',
                'viewer_url',
                'is_key_image',
            ]);
        });
    }
};
