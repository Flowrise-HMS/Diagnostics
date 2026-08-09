<?php

namespace Modules\Diagnostics\Providers;

use Filament\Pages\Page;
use Modules\Core\Classes\Support\PageWidgetsRegistry;
use Modules\Core\Support\ModuleAvailability;
use Modules\Core\Support\OptionalClass;
use Modules\Diagnostics\Filament\Widgets\CompletedDiagnosticResultsWidget;
use Modules\Diagnostics\Filament\Widgets\PendingDiagnosticFulfillmentsWidget;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Support\ModuleServiceProvider;

class DiagnosticsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Diagnostics';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'diagnostics';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->registerClinicalWorkspaceWidgets();
    }

    protected function registerClinicalWorkspaceWidgets(): void
    {
        if (! $this->app->bound(PageWidgetsRegistry::class)) {
            return;
        }

        if (! Module::isEnabled('Clinical') || ! ModuleAvailability::diagnosticsEnabled()) {
            return;
        }

        $registry = $this->app->make(PageWidgetsRegistry::class);

        $clinicalWorkspace = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\ClinicalWorkspace';
        $patientProfile = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\PatientProfile';
        $timeline = 'Modules\\Clinical\\Filament\\Clusters\\Workspace\\Pages\\Timeline';

        $completedResultsClassFactory = function (Page $page): array {
            $widget = OptionalClass::whenCanView(CompletedDiagnosticResultsWidget::class, 'Diagnostics');

            return $widget ? [$widget] : [];
        };

        $completedFooterFactory = function (Page $page): array {
            return $this->makePatientScopedWidgets($page, [
                CompletedDiagnosticResultsWidget::class,
            ]);
        };

        $patientProfileFooterFactory = function (Page $page): array {
            return $this->makePatientScopedWidgets($page, [
                PendingDiagnosticFulfillmentsWidget::class,
                CompletedDiagnosticResultsWidget::class,
            ]);
        };

        foreach ([$clinicalWorkspace, $patientProfile, $timeline] as $pageClass) {
            if (! class_exists($pageClass)) {
                continue;
            }

            $registry->register($pageClass, 'completed_results', $completedResultsClassFactory);
        }

        foreach ([$clinicalWorkspace, $timeline] as $pageClass) {
            if (! class_exists($pageClass)) {
                continue;
            }

            $registry->register($pageClass, 'footer', $completedFooterFactory);
        }

        if (class_exists($patientProfile)) {
            $registry->register($patientProfile, 'footer', $patientProfileFooterFactory);
        }
    }

    /**
     * @param  list<class-string>  $widgetClasses
     * @return list<object>
     */
    protected function makePatientScopedWidgets(Page $page, array $widgetClasses): array
    {
        $patientId = data_get($page, 'currentPatient.id') ?? data_get($page, 'patientId');
        $encounterId = data_get($page, 'currentEncounter.id');

        if (blank($patientId)) {
            return [];
        }

        $widgets = [];

        foreach ($widgetClasses as $widgetClass) {
            $resolved = OptionalClass::whenCanView($widgetClass, 'Diagnostics');

            if ($resolved === null) {
                continue;
            }

            $widgets[] = $resolved::make([
                'patientId' => $patientId,
                'encounterId' => $encounterId,
            ]);
        }

        return $widgets;
    }
}
