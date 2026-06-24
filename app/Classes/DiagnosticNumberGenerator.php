<?php

namespace Modules\Diagnostics\Classes;

use Modules\Core\Models\Branch;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticNumberGenerator
{
    public function generateAccessionNumber(string $branchId): string
    {
        $branch = Branch::query()->whereKey($branchId)->lockForUpdate()->firstOrFail();
        $prefix = $this->branchPrefix($branch);
        $date = now()->format('Ymd');

        $count = DiagnosticFulfillment::query()
            ->where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->count();

        $sequence = $count + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    protected function branchPrefix(Branch $branch): string
    {
        $sanitized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $branch->code) ?? '');

        return $sanitized !== '' ? $sanitized : 'DX';
    }
}
