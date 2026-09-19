<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicateFieldKeys();

        Schema::table('diagnostic_result_template_fields', function (Blueprint $table) {
            $table->unique(['template_id', 'field_key'], 'diag_template_fields_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_result_template_fields', function (Blueprint $table) {
            $table->dropUnique('diag_template_fields_key_unique');
        });
    }

    /**
     * Keep the earliest-sorted row for any key duplicated within a template so the
     * unique index can be added on databases that predate it.
     */
    protected function removeDuplicateFieldKeys(): void
    {
        $duplicates = DB::table('diagnostic_result_template_fields')
            ->select('template_id', 'field_key')
            ->groupBy('template_id', 'field_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $ids = DB::table('diagnostic_result_template_fields')
                ->where('template_id', $duplicate->template_id)
                ->where('field_key', $duplicate->field_key)
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->pluck('id');

            DB::table('diagnostic_result_template_fields')
                ->whereIn('id', $ids->slice(1)->all())
                ->delete();
        }
    }
};
