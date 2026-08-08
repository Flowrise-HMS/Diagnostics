<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ViewDiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

class DiagnosticRecordTitleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);

        // Filament resource policies are gated by Shield permissions that aren't
        // seeded in the test database; bypass authorization for these UI tests.
        Gate::before(fn (): bool => true);

        $this->actingAs(User::factory()->create());

        Filament::setCurrentPanel(Filament::getDefaultPanel());
    }

    public function test_record_title_attributes_are_not_cast_to_enums(): void
    {
        foreach ([DiagnosticFulfillmentResource::class, DiagnosticServiceProfileResource::class] as $resource) {
            $attribute = $resource::getRecordTitleAttribute();
            $cast = (new ($resource::getModel()))->getCasts()[$attribute] ?? null;

            $this->assertFalse(
                $cast !== null && enum_exists($cast),
                "[{$resource}] must not use the enum-cast attribute [{$attribute}] as its record title.",
            );
        }
    }

    public function test_view_fulfillment_page_resolves_a_string_record_title(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'accession_number' => 'ACC-TITLE-100',
        ]);

        Livewire::test(ViewDiagnosticFulfillment::class, ['record' => $fulfillment->getKey()])
            ->assertOk()
            ->tap(fn ($page) => $this->assertSame('ACC-TITLE-100', $page->instance()->getRecordTitle()));
    }

    public function test_view_service_profile_page_resolves_a_string_record_title(): void
    {
        $profile = DiagnosticServiceProfile::factory()->create();

        Livewire::test(ViewDiagnosticServiceProfile::class, ['record' => $profile->getKey()])
            ->assertOk()
            ->tap(fn ($page) => $this->assertSame($profile->title, $page->instance()->getRecordTitle()));
    }
}
