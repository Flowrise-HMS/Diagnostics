<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Diagnostics\Database\Seeders\DiagnosticsDatabaseSeeder;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticMedia;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticStudy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiagnosticSupportInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('module:migrate', ['module' => 'Clinical', '--force' => true]);
        $this->artisan('module:migrate', ['module' => 'Diagnostics', '--force' => true]);
    }

    public function test_diagnostics_factories_can_build_connected_records(): void
    {
        $profile = DiagnosticServiceProfile::factory()->create();
        $template = DiagnosticResultTemplate::factory()->create([
            'profile_id' => $profile->id,
        ]);
        $field = DiagnosticResultTemplateField::factory()->create([
            'template_id' => $template->id,
        ]);

        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => $profile->discipline,
        ]);
        $specimen = DiagnosticSpecimen::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $observation = DiagnosticObservation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'specimen_id' => $specimen->id,
        ]);
        $reportVersion = DiagnosticReportVersion::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $reportVersion->observations()->attach($observation->id, ['sort_order' => 1]);

        $study = DiagnosticStudy::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $media = DiagnosticMedia::factory()->create([
            'study_id' => $study->id,
        ]);
        $resultFile = DiagnosticResultFile::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'report_version_id' => $reportVersion->id,
        ]);

        $this->assertTrue($template->profile->is($profile));
        $this->assertTrue($field->template->is($template));
        $this->assertTrue($specimen->fulfillment->is($fulfillment));
        $this->assertTrue($observation->specimen->is($specimen));
        $this->assertCount(1, $reportVersion->fresh()->observations);
        $this->assertTrue($media->study->is($study));
        $this->assertTrue($resultFile->reportVersion->is($reportVersion));
    }

    public function test_diagnostics_database_seeder_creates_and_assigns_custom_workflow_permissions(): void
    {
        foreach ([
            'super_admin',
            'laboratory_technician',
            'laboratory_scientist',
            'radiology_technician',
            'radiologist',
            'pathologist',
        ] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        $this->seed(DiagnosticsDatabaseSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'collect_diagnostic_specimen',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'sign_diagnostic_report',
            'guard_name' => 'api',
        ]);

        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('collect_diagnostic_specimen'));
        $this->assertTrue(Role::findByName('radiology_technician', 'web')->hasPermissionTo('upload_diagnostic_result_file'));
        $this->assertTrue(Role::findByName('pathologist', 'web')->hasPermissionTo('sign_diagnostic_report'));
        $this->assertTrue(Role::findByName('radiologist', 'web')->hasPermissionTo('verify_diagnostic_result'));
    }
}
