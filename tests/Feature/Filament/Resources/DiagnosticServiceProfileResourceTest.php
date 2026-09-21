<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\CreateDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\EditDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ListDiagnosticServiceProfiles;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ViewDiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('Diagnostics');
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
    $this->service = $this->nonDiagnosticService();
});

FilamentResourceTestSuite::register([
    'resource' => DiagnosticServiceProfileResource::class,
    'subject' => 'DiagnosticServiceProfile',
    'model' => DiagnosticServiceProfile::class,
    'listPage' => ListDiagnosticServiceProfiles::class,
    'createPage' => CreateDiagnosticServiceProfile::class,
    'editPage' => EditDiagnosticServiceProfile::class,
    'viewPage' => ViewDiagnosticServiceProfile::class,
    'filter' => [
        'name' => 'discipline',
        'value' => DiagnosticDiscipline::LAB->value,
        'attribute' => 'discipline',
    ],
    'hasBulkDelete' => true,
    'hasRecordDelete' => true,
    'makeRecord' => fn (TestCase $test, array $attributes = []): DiagnosticServiceProfile => DiagnosticServiceProfile::factory()->create([
        'discipline' => DiagnosticDiscipline::LAB,
        ...$attributes,
    ]),
    'makeRecords' => fn (TestCase $test, int $count) => DiagnosticServiceProfile::factory()->count($count)->create([
        'discipline' => DiagnosticDiscipline::LAB,
    ]),
    'createForm' => fn (TestCase $test): array => [
        'service_id' => $test->service->id,
        'discipline' => DiagnosticDiscipline::LAB->value,
        'is_active' => true,
    ],
    'updateForm' => fn (): array => [
        'discipline' => DiagnosticDiscipline::LAB->value,
        'is_active' => true,
        'preparation_instructions' => 'Updated prep instructions',
    ],
    'schemaState' => fn (mixed $test, DiagnosticServiceProfile $record): array => [
        'discipline' => $record->discipline instanceof DiagnosticDiscipline ? $record->discipline->value : $record->discipline,
    ],
    'requiredValidation' => [
        'discipline is required' => [['discipline' => null], ['discipline' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'service_id' => $payload['service_id'],
        'discipline' => $payload['discipline'],
    ],
]);
