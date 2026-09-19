<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Enums\UserRole;
use Modules\Core\Models\Service;
use Modules\Core\Models\ServiceCategory;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ListDiagnosticServiceProfiles;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);
});

function profileForService(Service $service): ?DiagnosticServiceProfile
{
    return DiagnosticServiceProfile::query()
        ->withoutGlobalScope('branch')
        ->where('service_id', $service->id)
        ->first();
}

it('creates a diagnostic setup for services saved into diagnostic categories', function (string $code, DiagnosticDiscipline $expected): void {
    $category = $code === 'PAT'
        ? ServiceCategory::query()->firstOrCreate(['code' => 'PAT'], ['name' => 'Pathology', 'is_active' => true])
        : $this->serviceCategory(['code' => $code]);

    $service = Service::factory()->forCategory($category)->create(['price' => 42.5, 'is_billable' => true]);

    $profile = profileForService($service);

    expect($profile)->not->toBeNull()
        ->and($profile->discipline)->toBe($expected)
        ->and($profile->is_active)->toBeTrue()
        ->and($profile->branch_id)->toBe($service->branch_id)
        ->and(DiagnosticServiceProfile::query()->withoutGlobalScope('branch')->where('service_id', $service->id)->count())->toBe(1);

    // The service itself is never touched by the auto-setup.
    $service->refresh();
    expect((float) $service->price)->toBe(42.5)
        ->and($service->is_billable)->toBeTrue()
        ->and($service->is_active)->toBeTrue();
})->with([
    'laboratory' => [ServiceCategoryCode::LAB->value, DiagnosticDiscipline::LAB],
    'radiology' => [ServiceCategoryCode::RAD->value, DiagnosticDiscipline::RADIOLOGY],
    'pathology (non-enum code)' => ['PAT', DiagnosticDiscipline::PATHOLOGY],
    'generic diagnostics' => [ServiceCategoryCode::DIA->value, DiagnosticDiscipline::LAB],
]);

it('does not create a setup for services outside diagnostic categories', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::CON->value]))
        ->create();

    expect(profileForService($service))->toBeNull();
});

it('leaves an existing setup untouched when the service is re-saved or re-categorised', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]))
        ->create();

    $profile = profileForService($service);
    $profile->update(['discipline' => DiagnosticDiscipline::PATHOLOGY, 'default_specimen_type' => 'tissue']);

    $service->update(['name' => 'Renamed test']);
    $service->update(['category_id' => $this->serviceCategory(['code' => ServiceCategoryCode::RAD->value])->id]);

    $fresh = profileForService($service);

    expect($fresh->id)->toBe($profile->id)
        ->and($fresh->discipline)->toBe(DiagnosticDiscipline::PATHOLOGY)
        ->and($fresh->default_specimen_type)->toBe('tissue');
});

it('backfills missing setups from the service catalog idempotently', function (): void {
    $lab = $this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]);

    $withoutSetup = Service::withoutEvents(fn (): Service => Service::factory()->forCategory($lab)->create());
    $inactive = Service::withoutEvents(fn (): Service => Service::factory()->forCategory($lab)->inactive()->create());
    $alreadySetUp = Service::factory()->forCategory($lab)->create();
    $serviceCount = Service::query()->withoutGlobalScope('branch')->count();

    expect(profileForService($withoutSetup))->toBeNull();

    $this->artisan('diagnostics:sync-profiles')
        ->expectsOutputToContain('Created 1 diagnostic service(s)')
        ->assertSuccessful();

    expect(profileForService($withoutSetup))->not->toBeNull()
        ->and(profileForService($inactive))->toBeNull()
        ->and(profileForService($alreadySetUp))->not->toBeNull()
        ->and(Service::query()->withoutGlobalScope('branch')->count())->toBe($serviceCount);

    $second = app(DiagnosticCatalogService::class)->syncProfilesFromServiceCatalog();

    expect($second)->toBe(['created' => 0, 'existing' => 2]);
});

it('offers the catalog sync only to super admins on the list page', function (): void {
    Gate::before(fn (): bool => true);
    Filament::setCurrentPanel(Filament::getDefaultPanel());

    $lab = $this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]);
    $service = Service::withoutEvents(fn (): Service => Service::factory()->forCategory($lab)->create());

    $staff = User::factory()->create(['branch_id' => null]);

    Livewire::actingAs($staff)
        ->test(ListDiagnosticServiceProfiles::class)
        ->assertActionHidden('syncFromCatalog');

    Role::findOrCreate(UserRole::SUPER_ADMIN->value, 'web');
    $admin = User::factory()->create(['branch_id' => null]);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    Livewire::actingAs($admin)
        ->test(ListDiagnosticServiceProfiles::class)
        ->assertActionVisible('syncFromCatalog')
        ->callAction('syncFromCatalog')
        ->assertNotified('Diagnostic services synced');

    expect(profileForService($service))->not->toBeNull();
});
