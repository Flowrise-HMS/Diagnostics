<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

class DiagnosticDomainContractTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules();
    }

    public function test_diagnostic_service_profile_is_a_one_to_one_extension_of_service(): void
    {
        $service = Service::factory()->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'loinc_code' => '58410-2',
            'loinc_display' => 'CBC panel - Blood',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('diagnostic_service_profiles', [
            'id' => $profile->id,
            'service_id' => $service->id,
            'discipline' => 'lab',
        ]);

        $this->assertTrue($profile->service->is($service));
    }

    public function test_result_file_belongs_to_fulfillment_and_report_link_is_optional(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $user = User::factory()->create();

        $file = DiagnosticResultFile::create([
            'fulfillment_id' => $fulfillment->id,
            'report_version_id' => null,
            'file_type' => 'pdf',
            'source' => 'external_lab',
            'file_name' => 'cbc-report.pdf',
            'file_path' => 'diagnostics/reports/cbc-report.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $user->id,
        ]);

        $this->assertDatabaseHas('diagnostic_result_files', [
            'id' => $file->id,
            'fulfillment_id' => $fulfillment->id,
            'report_version_id' => null,
            'source' => 'external_lab',
        ]);
    }

    public function test_profile_can_have_one_default_result_template_for_fast_entry(): void
    {
        $service = Service::factory()->create();
        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Default FBC',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'hemoglobin',
            'label' => 'Hemoglobin',
            'value_type' => 'numeric',
            'sort_order' => 1,
        ]);

        $this->assertTrue($profile->defaultTemplate->is($template));
        $this->assertCount(1, $template->fields);
    }

    public function test_fulfillment_defaults_to_pending_status(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();

        $this->assertSame('pending', $fulfillment->status->value);
    }
}
