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
            'laboratory_scientist',
            'radiology_technician',
            'radiologist',
            'pathologist',
        ],
        'collect_diagnostic_specimen' => [
            'super_admin',
            'laboratory_technician',
            'laboratory_scientist',
            'pathologist',
        ],
        'upload_diagnostic_result_file' => [
            'super_admin',
            'laboratory_technician',
            'laboratory_scientist',
            'radiology_technician',
            'radiologist',
            'pathologist',
        ],
        'finalize_diagnostic_result' => [
            'super_admin',
            'laboratory_technician',
            'laboratory_scientist',
            'radiology_technician',
            'radiologist',
            'pathologist',
        ],
        'verify_diagnostic_result' => [
            'super_admin',
            'laboratory_scientist',
            'radiologist',
            'pathologist',
        ],
        'sign_diagnostic_report' => [
            'super_admin',
            'laboratory_scientist',
            'radiologist',
            'pathologist',
        ],
        'amend_diagnostic_report' => [
            'super_admin',
            'laboratory_scientist',
            'radiologist',
            'pathologist',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys($this->matrix) as $name) {
            foreach (['web', 'api'] as $guard) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        foreach ($this->matrix as $permissionName => $roles) {
            $permission = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission === null) {
                continue;
            }

            foreach ($roles as $roleName) {
                Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->first()
                    ?->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
