<?php

declare(strict_types=1);

namespace Modules\Diagnostics\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;

class DiagnosticResultTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny DiagnosticResultTemplate');
    }

    public function view(AuthUser $authUser, DiagnosticResultTemplate $diagnosticResultTemplate): bool
    {
        return $authUser->can('View DiagnosticResultTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create DiagnosticResultTemplate');
    }

    public function update(AuthUser $authUser, DiagnosticResultTemplate $diagnosticResultTemplate): bool
    {
        return $authUser->can('Update DiagnosticResultTemplate');
    }

    public function delete(AuthUser $authUser, DiagnosticResultTemplate $diagnosticResultTemplate): bool
    {
        return $authUser->can('Delete DiagnosticResultTemplate');
    }

    public function restore(AuthUser $authUser, DiagnosticResultTemplate $diagnosticResultTemplate): bool
    {
        return $authUser->can('Restore DiagnosticResultTemplate');
    }

    public function forceDelete(AuthUser $authUser, DiagnosticResultTemplate $diagnosticResultTemplate): bool
    {
        return $authUser->can('ForceDelete DiagnosticResultTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny DiagnosticResultTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny DiagnosticResultTemplate');
    }

    public function replicate(AuthUser $authUser, DiagnosticResultTemplate $diagnosticResultTemplate): bool
    {
        return $authUser->can('Replicate DiagnosticResultTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder DiagnosticResultTemplate');
    }
}
