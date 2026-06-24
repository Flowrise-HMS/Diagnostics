<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_report_signatures', function (Blueprint $table) {
            $table->string('signature_type')->nullable()->after('signed_by');
            $table->text('signature')->nullable()->after('signature_type');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_report_signatures', function (Blueprint $table) {
            $table->dropColumn(['signature_type', 'signature']);
        });
    }
};
