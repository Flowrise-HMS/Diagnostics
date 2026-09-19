<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Context;
use Modules\Core\Models\Branch;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticStudy;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

/*
 * BelongsToBranch adds `where branch_id = ?` to every query once a current branch is set.
 * Catalog children (ranges, panels, templates) have no branch column and must never be
 * filtered; studies now carry one and inherit it from their fulfillment.
 */
beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    $this->branch = Branch::factory()->default()->create();
    Context::add('current_branch_id', $this->branch->id);
});

afterEach(function (): void {
    Context::forget('current_branch_id');
});

it('reads and writes reference ranges and panels while a branch is active', function (): void {
    $profile = DiagnosticServiceProfile::factory()->create(['branch_id' => $this->branch->id]);

    $range = DiagnosticReferenceRange::factory()->create(['profile_id' => $profile->id]);
    $panel = DiagnosticPanel::factory()->create(['profile_id' => $profile->id]);
    $item = DiagnosticPanelItem::factory()->create(['panel_id' => $panel->id]);

    expect(DiagnosticReferenceRange::query()->whereKey($range->id)->exists())->toBeTrue()
        ->and($profile->fresh()->referenceRanges)->toHaveCount(1)
        ->and(DiagnosticPanel::query()->whereKey($panel->id)->exists())->toBeTrue()
        ->and(DiagnosticPanelItem::query()->whereKey($item->id)->exists())->toBeTrue();
});

it('resolves a template without a branch for a branch-scoped profile', function (): void {
    $profile = DiagnosticServiceProfile::factory()->create(['branch_id' => $this->branch->id]);

    $template = DiagnosticResultTemplate::factory()->create([
        'profile_id' => $profile->id,
        'branch_id' => null,
        'is_default' => true,
        'is_active' => true,
    ]);
    DiagnosticResultTemplateField::factory()->create(['template_id' => $template->id]);

    expect($profile->fresh()->defaultTemplate?->id)->toBe($template->id)
        ->and($profile->fresh()->defaultTemplate->fields)->toHaveCount(1);
});

it('creates studies with the fulfillment branch while a branch is active', function (): void {
    // The fulfillment factory builds a request chain that is itself branch-scoped.
    Context::forget('current_branch_id');
    $fulfillment = DiagnosticFulfillment::factory()->create(['branch_id' => $this->branch->id]);
    Context::add('current_branch_id', $this->branch->id);

    $study = DiagnosticStudy::factory()->create([
        'fulfillment_id' => $fulfillment->id,
        'branch_id' => $fulfillment->branch_id,
    ]);

    expect(DiagnosticStudy::query()->whereKey($study->id)->exists())->toBeTrue()
        ->and($fulfillment->fresh()->study?->id)->toBe($study->id)
        ->and($study->branch_id)->toBe($this->branch->id);
});
