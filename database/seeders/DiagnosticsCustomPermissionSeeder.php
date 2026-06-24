<?php

namespace Modules\Diagnostics\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DiagnosticsCustomPermissionSeeder extends Seeder
{
    /** @var array<string, string[]> */
    protected array $matrix = [
        'assign_diagnostic_fulfillment' => [
            'super_admin',
            'laboratory_technician',
        ],
        'collect_diagnostic_specimen' => [
            'super_admin',
            'laboratory_technician',
        ],
        'upload_diagnostic_result_file' => [
            'super_admin',
            'laboratory_technician',
        ],
        'finalize_diagnostic_result' => [
            'super_admin',
            'laboratory_technician',
        ],
        'verify_diagnostic_result' => [
            'super_admin',
            'laboratory_technician',
        ],
        'sign_diagnostic_report' => [
            'super_admin',
            'laboratory_technician',
        ],
        'amend_diagnostic_report' => [
            'super_admin',
            'laboratory_technician',
        ],
        'manage_diagnostic_panels' => [
            'super_admin',
        ],
        'manage_diagnostic_reference_ranges' => [
            'super_admin',
        ],
        'record_structured_diagnostic_observations' => [
            'super_admin',
            'laboratory_technician',
        ],
        'manage_diagnostic_allocations' => [
            'super_admin',
            'laboratory_technician',
        ],
        'manage_diagnostic_specimen_processing' => [
            'super_admin',
            'laboratory_technician',
        ],
        'print_diagnostic_lab_result' => [
            'super_admin',
            'laboratory_technician',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->matrix as $permissionName => $roles) {
            Permission::findOrCreate($permissionName, 'web');

            foreach ($roles as $roleName) {
                Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->first()
                    ?->givePermissionTo($permissionName);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
