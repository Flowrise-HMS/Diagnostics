<?php

declare(strict_types=1);

namespace Modules\Diagnostics\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticFulfillmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny DiagnosticFulfillment');
    }

    public function view(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('View DiagnosticFulfillment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create DiagnosticFulfillment');
    }

    public function update(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('Update DiagnosticFulfillment');
    }

    public function delete(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('Delete DiagnosticFulfillment');
    }

    public function restore(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('Restore DiagnosticFulfillment');
    }

    public function forceDelete(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('ForceDelete DiagnosticFulfillment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny DiagnosticFulfillment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny DiagnosticFulfillment');
    }

    public function replicate(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('Replicate DiagnosticFulfillment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder DiagnosticFulfillment');
    }

    public function collectSpecimen(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('collect_diagnostic_specimen');
    }

    public function uploadResultFile(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('upload_diagnostic_result_file');
    }

    public function finalizeResult(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('finalize_diagnostic_result');
    }

    public function verifyResult(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('verify_diagnostic_result');
    }

    public function signReport(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('sign_diagnostic_report');
    }

    public function amendReport(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('amend_diagnostic_report');
    }

    public function assign(AuthUser $authUser, DiagnosticFulfillment $diagnosticFulfillment): bool
    {
        return $authUser->can('assign_diagnostic_fulfillment');
    }
}
