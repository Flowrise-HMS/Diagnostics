<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ListDiagnosticServiceProfiles;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ViewDiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

class DiagnosticServiceProfileLazyLoadingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);

        Gate::before(fn (): bool => true);

        $this->actingAs(User::factory()->create());

        Filament::setCurrentPanel(Filament::getDefaultPanel());
    }

    public function test_list_page_renders_default_template_column_without_lazy_loading(): void
    {
        Model::preventLazyLoading();

        $profile = DiagnosticServiceProfile::factory()->create();
        DiagnosticServiceProfile::factory()->create();
        $template = DiagnosticResultTemplate::factory()->create([
            'profile_id' => $profile->id,
            'name' => 'FBC Panel',
            'is_default' => true,
            'is_active' => true,
        ]);

        Livewire::test(ListDiagnosticServiceProfiles::class)
            ->assertOk()
            ->tap(function ($page) use ($profile, $template): void {
                $record = $page->instance()->getTableRecord($profile->getKey());

                $this->assertSame(
                    $template->name,
                    $page->instance()->getTable()->getColumn('defaultTemplate.name')->record($record)->getStateFromRecord(),
                );
            });
    }

    public function test_view_page_renders_default_template_entry_without_lazy_loading(): void
    {
        Model::preventLazyLoading();

        $profile = DiagnosticServiceProfile::factory()->create();
        DiagnosticResultTemplate::factory()->create([
            'profile_id' => $profile->id,
            'name' => 'FBC Panel',
            'is_default' => true,
            'is_active' => true,
        ]);

        Livewire::test(ViewDiagnosticServiceProfile::class, ['record' => $profile->getKey()])
            ->assertOk();
    }

    public function test_get_template_fields_does_not_lazy_load_default_template(): void
    {
        Model::preventLazyLoading();

        $profile = DiagnosticServiceProfile::factory()->create();
        DiagnosticServiceProfile::factory()->create();
        DiagnosticResultTemplate::factory()->create([
            'profile_id' => $profile->id,
            'name' => 'FBC Panel',
            'is_default' => true,
            'is_active' => true,
        ]);

        $freshProfile = DiagnosticServiceProfile::query()->get()->firstWhere('id', $profile->id);

        $fields = app(DiagnosticResultService::class)->getTemplateFields($freshProfile);

        $this->assertCount(0, $fields);
    }

    public function test_panel_submit_does_not_lazy_load_child_profile_default_templates(): void
    {
        Model::preventLazyLoading();

        $user = User::factory()->create();
        $panelService = Service::factory()->create(['name' => 'Full Blood Count']);
        $hbService = Service::factory()->create(['name' => 'Hemoglobin']);
        $pltService = Service::factory()->create(['name' => 'Platelets']);

        $item = RequestItem::factory()->forService($panelService)->create();

        $panelProfile = DiagnosticServiceProfile::create([
            'service_id' => $panelService->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $hbProfile = DiagnosticServiceProfile::create([
            'service_id' => $hbService->id,
            'discipline' => 'lab',
            'loinc_code' => 'hemoglobin',
            'loinc_display' => 'Hemoglobin',
            'is_active' => true,
        ]);

        $pltProfile = DiagnosticServiceProfile::create([
            'service_id' => $pltService->id,
            'discipline' => 'lab',
            'loinc_code' => 'platelet',
            'loinc_display' => 'Platelets',
            'is_active' => true,
        ]);

        DiagnosticResultTemplate::create([
            'profile_id' => $hbProfile->id,
            'name' => 'Hb Template',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplate::create([
            'profile_id' => $pltProfile->id,
            'name' => 'PLT Template',
            'is_default' => true,
            'is_active' => true,
        ]);

        $panel = DiagnosticPanel::create(['profile_id' => $panelProfile->id]);

        DiagnosticPanelItem::create([
            'panel_id' => $panel->id,
            'child_profile_id' => $hbProfile->id,
            'sequence' => 1,
            'is_required' => true,
        ]);

        DiagnosticPanelItem::create([
            'panel_id' => $panel->id,
            'child_profile_id' => $pltProfile->id,
            'sequence' => 2,
            'is_required' => true,
        ]);

        app(DiagnosticResultService::class)->submit($item, [
            'results' => [
                ['key' => 'hemoglobin', 'value' => '14.2'],
                ['key' => 'platelet', 'value' => '180'],
            ],
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $this->assertSame(
            2,
            DiagnosticObservation::query()->where('fulfillment_id', $fulfillment->id)->count(),
        );
    }
}
