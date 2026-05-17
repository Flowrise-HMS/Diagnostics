<?php

namespace Modules\Diagnostics\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DiagnosticsShieldPermissionsSeeder extends Seeder
{
    /** @var list<string> */
    protected array $resourcePermissions = [
        'ViewAny DiagnosticFulfillment',
        'View DiagnosticFulfillment',
        'Create DiagnosticFulfillment',
        'Update DiagnosticFulfillment',
        'Delete DiagnosticFulfillment',
        'Restore DiagnosticFulfillment',
        'ForceDelete DiagnosticFulfillment',
        'ForceDeleteAny DiagnosticFulfillment',
        'RestoreAny DiagnosticFulfillment',
        'Replicate DiagnosticFulfillment',
        'Reorder DiagnosticFulfillment',
        'ViewAny DiagnosticServiceProfile',
        'View DiagnosticServiceProfile',
        'Create DiagnosticServiceProfile',
        'Update DiagnosticServiceProfile',
        'Delete DiagnosticServiceProfile',
        'Restore DiagnosticServiceProfile',
        'ForceDelete DiagnosticServiceProfile',
        'ForceDeleteAny DiagnosticServiceProfile',
        'RestoreAny DiagnosticServiceProfile',
        'Replicate DiagnosticServiceProfile',
        'Reorder DiagnosticServiceProfile',
        'ViewAny DiagnosticResultTemplate',
        'View DiagnosticResultTemplate',
        'Create DiagnosticResultTemplate',
        'Update DiagnosticResultTemplate',
        'Delete DiagnosticResultTemplate',
        'Restore DiagnosticResultTemplate',
        'ForceDelete DiagnosticResultTemplate',
        'ForceDeleteAny DiagnosticResultTemplate',
        'RestoreAny DiagnosticResultTemplate',
        'Replicate DiagnosticResultTemplate',
        'Reorder DiagnosticResultTemplate',
        'View DiagnosticsCluster',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->giveNamedPermissionsToRole('super_admin', $this->resourcePermissions);
        $this->giveNamedPermissionsToRole('laboratory_technician', [
            'View DiagnosticsCluster',
            'ViewAny DiagnosticFulfillment',
            'View DiagnosticFulfillment',
            'Update DiagnosticFulfillment',
            'ViewAny DiagnosticServiceProfile',
            'View DiagnosticServiceProfile',
            'ViewAny DiagnosticResultTemplate',
            'View DiagnosticResultTemplate',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $names
     */
    protected function giveNamedPermissionsToRole(string $roleName, array $names): void
    {
        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            return;
        }

        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->pluck('name')
            ->all();

        if ($existing === []) {
            return;
        }

        $role->givePermissionTo($existing);
    }
}
