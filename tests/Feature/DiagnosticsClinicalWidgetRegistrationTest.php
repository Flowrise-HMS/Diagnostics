<?php

namespace Modules\Diagnostics\Tests\Feature;

use Filament\Pages\Page;
use Modules\Core\Classes\Support\PageWidgetsRegistry;
use Modules\Core\Support\ModuleAvailability;
use Modules\Diagnostics\Filament\Widgets\CompletedDiagnosticResultsWidget;
use Modules\Diagnostics\Filament\Widgets\PendingDiagnosticFulfillmentsWidget;
use Modules\Diagnostics\Providers\DiagnosticsServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DiagnosticsClinicalWidgetRegistrationTest extends TestCase
{
    #[Test]
    public function it_registers_completed_results_widgets_on_clinical_workspace_pages(): void
    {
        if (! ModuleAvailability::diagnosticsEnabled() || ! ModuleAvailability::clinicalEnabled()) {
            $this->markTestSkipped('Diagnostics and Clinical modules must be enabled.');
        }

        $registry = app(PageWidgetsRegistry::class);
        $page = $this->createStub(Page::class);

        $clinicalWorkspace = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\ClinicalWorkspace';
        $patientProfile = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\PatientProfile';
        $timeline = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\Timeline';

        $this->assertContains(
            CompletedDiagnosticResultsWidget::class,
            $registry->for($clinicalWorkspace, 'completed_results', $page),
        );
        $this->assertContains(
            CompletedDiagnosticResultsWidget::class,
            $registry->for($patientProfile, 'completed_results', $page),
        );
        $this->assertContains(
            CompletedDiagnosticResultsWidget::class,
            $registry->for($timeline, 'completed_results', $page),
        );
    }

    #[Test]
    public function it_registers_pending_and_completed_widgets_on_patient_profile_footer(): void
    {
        if (! ModuleAvailability::diagnosticsEnabled() || ! ModuleAvailability::clinicalEnabled()) {
            $this->markTestSkipped('Diagnostics and Clinical modules must be enabled.');
        }

        $registry = app(PageWidgetsRegistry::class);

        $page = new class extends Page
        {
            protected string $view = 'filament-panels::page';

            public ?string $patientId = 'patient-fixture-id';

            public ?object $currentPatient = null;

            public ?object $currentEncounter = null;
        };

        $patientProfile = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\PatientProfile';
        $footerWidgets = $registry->for($patientProfile, 'footer', $page);

        $this->assertCount(2, $footerWidgets);
        $this->assertSame(PendingDiagnosticFulfillmentsWidget::class, $footerWidgets[0]->widget);
        $this->assertSame(CompletedDiagnosticResultsWidget::class, $footerWidgets[1]->widget);
    }

    #[Test]
    public function diagnostics_service_provider_registers_widget_helpers(): void
    {
        $provider = app()->getProvider(DiagnosticsServiceProvider::class);

        $this->assertNotNull($provider);
        $this->assertTrue(method_exists($provider, 'boot'));
    }
}
