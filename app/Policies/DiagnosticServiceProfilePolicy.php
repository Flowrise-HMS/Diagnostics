<?php

declare(strict_types=1);

namespace Modules\Diagnostics\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticServiceProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny DiagnosticServiceProfile');
    }

    public function view(AuthUser $authUser, DiagnosticServiceProfile $diagnosticServiceProfile): bool
    {
        return $authUser->can('View DiagnosticServiceProfile');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create DiagnosticServiceProfile');
    }

    public function update(AuthUser $authUser, DiagnosticServiceProfile $diagnosticServiceProfile): bool
    {
        return $authUser->can('Update DiagnosticServiceProfile');
    }

    public function delete(AuthUser $authUser, DiagnosticServiceProfile $diagnosticServiceProfile): bool
    {
        return $authUser->can('Delete DiagnosticServiceProfile');
    }

    public function restore(AuthUser $authUser, DiagnosticServiceProfile $diagnosticServiceProfile): bool
    {
        return $authUser->can('Restore DiagnosticServiceProfile');
    }

    public function forceDelete(AuthUser $authUser, DiagnosticServiceProfile $diagnosticServiceProfile): bool
    {
        return $authUser->can('ForceDelete DiagnosticServiceProfile');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny DiagnosticServiceProfile');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny DiagnosticServiceProfile');
    }

    public function replicate(AuthUser $authUser, DiagnosticServiceProfile $diagnosticServiceProfile): bool
    {
        return $authUser->can('Replicate DiagnosticServiceProfile');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder DiagnosticServiceProfile');
    }
}
