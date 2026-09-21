<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\EditDiagnosticFulfillment;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ListDiagnosticFulfillments;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('Diagnostics');
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);
});

FilamentResourceTestSuite::register([
    'resource' => DiagnosticFulfillmentResource::class,
    'subject' => 'DiagnosticFulfillment',
    'model' => DiagnosticFulfillment::class,
    'listPage' => ListDiagnosticFulfillments::class,
    'editPage' => EditDiagnosticFulfillment::class,
    'viewPage' => ViewDiagnosticFulfillment::class,
    'filter' => [
        'name' => 'status',
        'value' => FulfillmentStatus::PENDING->value,
        'attribute' => 'status',
    ],
    'hasBulkDelete' => false,
    'hasRecordDelete' => false,
    'hasTableDelete' => true,
    'softDeletes' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): DiagnosticFulfillment => DiagnosticFulfillment::factory()->create([
        'branch_id' => $test->branch->id,
        'status' => FulfillmentStatus::PENDING,
        ...$attributes,
    ]),
    'makeRecords' => fn (TestCase $test, int $count) => DiagnosticFulfillment::factory()->count($count)->create([
        'branch_id' => $test->branch->id,
        'status' => FulfillmentStatus::PENDING,
    ]),
    'updateForm' => fn (): array => [
        'status' => FulfillmentStatus::PENDING->value,
    ],
    'schemaState' => fn (mixed $test, DiagnosticFulfillment $record): array => [
        'status' => $record->status,
    ],
]);
