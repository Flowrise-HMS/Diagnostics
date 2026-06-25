# Diagnostics Module

> Unified laboratory, radiology, and pathology workflows for FlowRise HMS.

The `Diagnostics` module extends the main clinical ordering flow instead of replacing it. Clinicians still order services through Clinical. Diagnostics takes those ordered items, turns them into operational fulfillments, and gives lab, radiology, and pathology teams a dedicated place to work on specimens, files, reports, and study records.

This document is written as the canonical guide for the module. It is meant to be useful to:

- clinical and diagnostic staff who need to understand how work moves through the system
- administrators who need to seed, configure, and govern the module
- developers who need to extend or maintain the module safely

## Current Status

Diagnostics is **complete** for operational use (HL7 and FHIR export remain deferred).

What is available:

- Clinical-to-Diagnostics bridge from `RequestItem` with accession numbers, priority, and discipline stubs
- full in-scope schema: profiles, panels, reference ranges, fulfillments, specimens, observations, components, reports, files, studies, media, allocations
- structured observation persistence via `DiagnosticObservationWriter` linked to report versions
- Filament operations: fulfillment worklist, relation managers, discipline-aware actions, structured result entry, lab result printing
- Clinical workspace template-driven lab result entry via `FulfillmentService`
- catalog admin: panels and reference ranges under service profiles; aligned template fields
- starter seed data for common lab, radiology, and pathology services

Explicitly deferred (global interoperability — last across all modules):

- HL7 message log / MLLP / LIS orchestration
- FHIR export transformers (`DiagnosticReport`, `Specimen`, `ImagingStudy`)

Use this README as both:

- the mental model for how Diagnostics is supposed to work
- the implementation map for what exists right now

## Mental Model

### The shortest explanation

Think of Diagnostics as the **fulfillment layer** for ordered diagnostic services.

- **Clinical** decides *what was ordered*
- **Diagnostics** manages *how the order is worked, documented, and released*
- **Billing** still anchors on the original ordered line item

### The core chain

```text
ServiceRequest -> RequestItem -> Task -> DiagnosticFulfillment
```

The meaning of each layer:

- `ServiceRequest`: the order header, linked to encounter context or a guest walk-in
- `RequestItem`: the individual billable diagnostic line such as FBC, Chest X-Ray, or Histopathology
- `Task`: operational queue/work pickup in Clinical
- `DiagnosticFulfillment`: the diagnostics-side work order where specimens, observations, reports, result files, and studies live

### Why this matters

This separation keeps the system easy to reason about:

- clinicians do not need a second order-entry system
- diagnostics staff get a dedicated workflow surface
- billing remains stable because it stays tied to the original ordered item
- external result files can be handled without forcing full structured transcription

### The working picture

```mermaid
flowchart LR
    SR["Clinical ServiceRequest"] --> RI["Clinical RequestItem"]
    RI --> T["Clinical Task"]
    RI --> F["DiagnosticFulfillment"]
    F --> S["DiagnosticSpecimen"]
    F --> O["DiagnosticObservation"]
    F --> R["DiagnosticReportVersion"]
    F --> RF["DiagnosticResultFile"]
    F --> ST["DiagnosticStudy"]
    ST --> M["DiagnosticMedia"]
    DSP["DiagnosticServiceProfile"] --> TRT["DiagnosticResultTemplate"]
```

## The Operating Model

### What the module is designed to optimize for

The module is deliberately small-clinic-first:

- the fastest safe path should be the normal path
- uploading an outside result is a valid first-class workflow
- structured entry is encouraged where it helps speed, reporting, and consistency
- templates exist to reduce repetitive typing, not to make staff think like data modelers
- walk-in diagnostics must work even if the person is not yet a registered patient

### Three valid result modes

Diagnostics is designed around three mental models for result capture:

1. **Structured-only**
   Staff enter a result into a template-driven structure and the system stores the diagnostic data as normalized records.

2. **File-only**
   Staff upload an external PDF, DOCX, or image and mark the fulfillment/report appropriately.

3. **Mixed**
   Staff upload a file and also capture key structured values in the system.

This flexibility is important in environments where some tests are performed in-house while others come from outside laboratories or external imaging centers.

Today, all three result modes are supported in production: structured values persist to `diagnostic_observations`, files attach to report versions, and mixed entry is available from Clinical and the fulfillment worklist.

## Filament UI Model (Hub, Not One Resource Per Table)

Diagnostics intentionally exposes **three sidebar resources**, not one per database table. Child entities are managed in context via relation managers, matching the Core/Billing pattern (few resources, deep nesting).

```text
DiagnosticServiceProfile (catalog)
  ├── Panel Items relation manager      → diagnostic_panel_items (+ diagnostic_panels)
  └── Reference Ranges relation manager → diagnostic_reference_ranges

DiagnosticResultTemplate (catalog)
  └── Template fields on form           → diagnostic_result_template_fields

DiagnosticFulfillment (operations worklist)
  ├── Specimens (+ containers, processing events)
  ├── Observations
  ├── Studies
  ├── Media (via study)
  ├── Allocations
  ├── Report Versions
  └── Result Files
```

There is **no** standalone Filament resource for panels, observations, specimens, or studies. Those records are always edited on a profile or fulfillment.

## Current Filament Surface

The Diagnostics Filament cluster centers on three resources:

### 1. `DiagnosticFulfillment`

Operational worklist (`canCreate()` is false — fulfillments come from Clinical orders).

**Table:** request, service, patient/guest, accession, priority, scheduled_at, discipline, status, critical flag, counts.

**Workflow actions** (policy-gated, discipline-aware): schedule, collect specimen, start processing, finalize result, verify result, sign report, amend report, **Record Structured Results**, **Print Lab Result**.

**Relation managers:**

| Manager | Models touched |
|---------|----------------|
| Specimens | `DiagnosticSpecimen`, containers, processing events |
| Observations | `DiagnosticObservation` |
| Studies | `DiagnosticStudy` |
| Media | `DiagnosticMedia` (through study) |
| Allocations | `DiagnosticFulfillmentAllocation` |
| Report Versions | `DiagnosticReportVersion` (+ signatures via workflow) |
| Result Files | `DiagnosticResultFile` |

### 2. `DiagnosticServiceProfile`

Catalog extension linking a Core `Service` to diagnostic behavior.

**Fields:** discipline, LOINC, default specimen type, auto-verify, turnaround time, modality, preparation instructions.

**Relation managers:**

| Manager | Models touched |
|---------|----------------|
| Panel Items | `DiagnosticPanelItem` (panel auto-created via `ensurePanel()`) |
| Reference Ranges | `DiagnosticReferenceRange` (age/gender bands, critical limits) |

Record titles use the linked service name and discipline label (not the raw enum).

### 3. `DiagnosticResultTemplate`

Template definitions with spec-aligned fields: `observation_code`, `default_units`, `is_required`, optional template reference ranges.

## Core Services (Non-Filament)

| Service | Role |
|---------|------|
| `DiagnosticResultService` | Template schema delegation, submit/finalize, Task summary, file linking |
| `DiagnosticObservationWriter` | Persists structured results to observations + report-version pivot |
| `DiagnosticLabResultPrintService` | Printable lab report data (patient or guest) |
| `DiagnosticNumberGenerator` | Branch-scoped daily accession numbers (`Classes/`, not `Services/`) |

Filament form UI for result entry lives in `DiagnosticResultEntryForm` (not in the domain service).

Clinical consumes Diagnostics through `FulfillmentService` and the Clinical Workspace lab tab.

## How Diagnostics Fits Into Clinical Care

### Scenario 1: A normal outpatient diagnostic order

1. A clinician opens an encounter or relevant patient/guest context.
2. The clinician creates a `ServiceRequest`.
3. One or more diagnostic `RequestItem`s are added, such as:
   - `Full Blood Count (FBC)`
   - `Chest X-Ray`
4. Clinical queue/task logic creates the operational task context.
5. Diagnostics listens for the new `RequestItem`.
6. Diagnostics creates one `DiagnosticFulfillment` per diagnostic request item.
7. Diagnostic staff work from the Diagnostics worklist, not from the raw order tables.
8. The fulfillment accumulates the real diagnostic work:
   - specimen collection
   - uploaded result files
   - observations
   - reports
   - studies/media
9. Once the diagnostic work is finalized, the fulfillment reflects that status and the record becomes available for downstream review.

### Scenario 2: A walk-in guest comes only for a test

1. Staff create a guest-facing `ServiceRequest` with no linked patient record yet.
2. A diagnostic `RequestItem` is added as usual.
3. Diagnostics still creates a `DiagnosticFulfillment`.
4. The diagnostic work proceeds normally.
5. If that guest later becomes a full patient, their older guest-originated diagnostic data should be linked deliberately, not silently rewritten.

This preserves auditability and avoids accidental merges.

### Scenario 3: An external laboratory sends back a PDF

1. A diagnostic fulfillment already exists for the request item.
2. Staff open the fulfillment.
3. They upload the result file using the result-file relation manager.
4. Staff may optionally finalize, verify, sign, or amend the report depending on role and workflow.
5. The uploaded file remains attached to the fulfillment and can optionally be linked to a report version.

This is essential for clinics that depend on outside labs or imaging partners.

### Scenario 4: Histopathology or biopsy workflow

1. A biopsy or pathology-related diagnostic service is ordered.
2. A pathology `DiagnosticFulfillment` is created.
3. Staff can track the pathology work under the same fulfillment model used by lab/radiology.
4. Narrative-style reporting is handled through the report layer and optional signatures.

The workflow is intentionally lean in v1: it supports pathology as a first-class discipline without demanding a heavyweight LIS/RIS-style orchestration layer.

## What End Users Should Expect

### For clinicians

Clinicians should think:

> “I keep using Clinical to place diagnostic orders. Diagnostics is where the diagnostic departments work the order.”

The clinician does not need to understand the whole Diagnostics schema. The clinician mainly needs to know:

- diagnostics orders still begin in Clinical
- results may come back as structured data, files, or both
- diagnostic services are still ordinary ordered services from the clinical perspective

### For laboratory, radiology, and pathology staff

Diagnostic staff should think:

> “Every diagnostic order becomes a fulfillment. I work the fulfillment until a result is ready.”

The key questions they should be able to answer from a fulfillment are:

- what was ordered?
- for whom was it ordered?
- what discipline is this?
- has a specimen been collected?
- are there result files?
- what is the latest report version?
- can I perform the next action based on my permissions?

### For front desk / walk-in workflows

Staff should think:

> “A person can come only for a test. Diagnostics should still work even if full patient registration has not happened yet.”

The module is designed around that reality.

## Starter Seed Data

Diagnostics ships with starter seeding so new environments do not begin empty.

### What the starter seed does

The starter seeder:

- reuses existing Core lab/radiology services where names already exist
- adds a `Pathology` service category if needed
- creates missing but common small-clinic diagnostics
- creates `DiagnosticServiceProfile` records for seeded diagnostic services
- creates one default `DiagnosticResultTemplate` per seeded profile

### Example starter services

Laboratory starters include:

- `Full Blood Count (FBC)`
- `Urinalysis`
- `Malaria Test (RDT)`
- `Blood Glucose`
- `Typhoid Test`
- `Lipid Profile`
- `Liver Function Test`
- `Renal Function Test`
- `Electrolytes / Urea / Creatinine`
- `Pregnancy Test`
- `HIV Screening`
- `HBsAg`
- `HCV Screening`
- `Stool Microscopy`
- `Microscopy, Culture and Sensitivity`
- `HbA1c`

Radiology starters include:

- `Chest X-Ray`
- `Head CT Scan`
- `Abdominal Ultrasound`
- `ECG (Electrocardiogram)`
- `Pelvic Ultrasound`
- `Obstetric Ultrasound`

Pathology starters include:

- `Histopathology`
- `Cytology`
- `Biopsy Examination`

### Seeding principle

If a matching Core service already exists, Diagnostics enriches it instead of duplicating it. That keeps pricing and service identity stable.

## Permissions and Roles

Diagnostics uses both:

- Shield-style resource permissions for Filament access
- custom workflow permissions for non-CRUD operational actions

### Examples of workflow permissions

- `assign_diagnostic_fulfillment`
- `collect_diagnostic_specimen`
- `upload_diagnostic_result_file`
- `finalize_diagnostic_result`
- `verify_diagnostic_result`
- `sign_diagnostic_report`
- `amend_diagnostic_report`
- `manage_diagnostic_panels`
- `manage_diagnostic_reference_ranges`
- `record_structured_diagnostic_observations`
- `manage_diagnostic_allocations`
- `manage_diagnostic_specimen_processing`
- `print_diagnostic_lab_result`

Custom permissions are declared in `Modules/Diagnostics/config/config.php` and seeded by `DiagnosticsCustomPermissionSeeder`. Filament workflow actions use policy abilities (for example `collectSpecimen`, `recordStructuredResults`) rather than raw permission strings in UI code.

This means a role can be allowed to:

- see the fulfillment worklist
- upload result files
- finalize a result
- verify a result
- sign a report

without necessarily receiving unrestricted access to all other actions.

## Administrator Guide

### What must be configured first

Before staff can use Diagnostics effectively, an administrator should confirm:

- Core service categories and services are seeded
- Diagnostics migrations have run
- Diagnostics seeders have run
- users have the right roles and permissions
- diagnostic service profiles and templates are reviewed after starter seeding

### Recommended setup order

```text
1. Run Core migrations and seeders
2. Run Diagnostics migrations
3. Run Diagnostics seeders
4. Review starter services and prices
5. Review diagnostic service profiles
6. Review and edit default templates
7. Assign roles and permissions
8. Train staff on the fulfillment worklist
```

### Useful commands

```bash
php artisan module:migrate Diagnostics
php artisan db:seed --class="Modules\\Diagnostics\\Database\\Seeders\\DiagnosticsDatabaseSeeder"
php artisan test Modules/Diagnostics/tests
```

### Admin responsibilities after seeding

Starter data is meant to get a clinic moving quickly, not to replace local governance.

Admins should still review:

- local pricing
- service activation/deactivation
- which templates should remain default
- whether additional local-only diagnostic services are needed
- which roles should verify, sign, or amend reports

## Developer Guide

### Module boundaries

Diagnostics deliberately does **not** replace:

- Clinical ordering
- Billing line items
- Patient registration

Instead it extends them.

The module depends most directly on:

- `Core` for services, categories, branches, and shared platform concepts
- `Clinical` for `ServiceRequest`, `RequestItem`, `Task`, and request-item events

### Important providers and listeners

- `Modules\Diagnostics\Providers\DiagnosticsServiceProvider`
- `Modules\Diagnostics\Providers\EventServiceProvider`
- `Modules\Diagnostics\Listeners\CreateDiagnosticFulfillmentFromRequestItem`
- `Modules\Diagnostics\Listeners\CancelDiagnosticFulfillmentFromRequestItem`

These listeners are what keep the shared Clinical ordering backbone connected to Diagnostics work records.

### Current schema surface (19 in-scope tables)

| Table | Purpose |
|-------|---------|
| `diagnostic_service_profiles` | Service → discipline/profile config |
| `diagnostic_panels` | Panel header per profile |
| `diagnostic_panel_items` | Panel component profiles |
| `diagnostic_reference_ranges` | Population reference ranges |
| `diagnostic_result_templates` | Result entry templates |
| `diagnostic_result_template_fields` | Template field definitions |
| `diagnostic_fulfillments` | Operational work orders |
| `diagnostic_fulfillment_allocations` | Scheduling (radiology rooms/devices) |
| `diagnostic_specimens` | Specimen records |
| `diagnostic_specimen_containers` | Container sub-records |
| `diagnostic_specimen_processing_events` | Processing timeline |
| `diagnostic_observations` | Structured result values |
| `diagnostic_observation_components` | Composite observation parts (schema only; no dedicated UI) |
| `diagnostic_report_versions` | Report versioning |
| `diagnostic_report_observations` | Report ↔ observation pivot |
| `diagnostic_report_signatures` | Signatures (via workflow, not standalone RM) |
| `diagnostic_result_files` | Uploaded PDFs/images |
| `diagnostic_studies` | Radiology study metadata |
| `diagnostic_media` | Study media / key images |

**Deferred globally:** `diagnostic_hl7_messages` (HL7/LIS interoperability phase).

### Filament resource layout

Three resource roots under `Modules/Diagnostics/app/Filament/Clusters/Diagnostics/Resources/`:

- `DiagnosticFulfillments` — seven relation managers (see **Filament UI Model** above)
- `DiagnosticServiceProfiles` — panel items + reference ranges relation managers
- `DiagnosticResultTemplates`

### Tests

The module test suite (`Modules/Diagnostics/tests`) currently includes 78 tests covering domain contracts, schema, observation persistence, discipline workflows, migration rollback, lab result printing, permissions, and starter catalog seeding.

Run the module tests with:

```bash
php artisan test Modules/Diagnostics/tests
```

### Metadata and package files

Diagnostics is described by:

- `Modules/Diagnostics/composer.json`
- `Modules/Diagnostics/module.json`

Keep these accurate whenever the module boundary or purpose changes. They are the first signals for maintainers, tooling, and future packaging.

## How the Whole Workflow Was Intended to Feel

The intended operator experience is:

- clinicians keep ordering where they already work
- diagnostics staff live in a dedicated fulfillment queue
- templates reduce typing for common tests
- external files are never treated as second-class records
- radiology and pathology stay inside the same module boundary instead of being split into parallel systems too early

That creates one consistent story:

> “An order starts in Clinical, becomes work in Diagnostics, stays billable through the original request item, and returns results in the form that is most practical for the facility.”

This is the central mental model to preserve whenever the module evolves.

## Current Boundaries and Deferred Scope

**Implemented but without standalone Filament resources:**

- `DiagnosticPanel` — managed via Panel Items relation manager on service profiles
- `DiagnosticObservationComponent` — schema/factory only; composite values stored on parent observations
- `DiagnosticReportSignature` — created via sign-report workflow, not a separate admin tab

**Explicitly deferred (global interoperability — last across all modules):**

- HL7 message log / MLLP / LIS (`diagnostic_hl7_messages` migration slot reserved)
- FHIR export transformers (`DiagnosticReport`, `Specimen`, `ImagingStudy`)
- Heavy RIS/IHE procedure hierarchy
- Mandatory transcription of every uploaded external result into structured fields

## Related Documentation

Audience-specific companion documents live in the project docs tree:

- `docs/user-guide/diagnostics.md`
- `docs/admin-guide/diagnostics.md`
- `docs/developer-guide/diagnostics.md`

For approved design context, also see:

- `docs/superpowers/specs/2026-05-12-diagnostics-module-design.md`
- `docs/superpowers/plans/2026-05-12-diagnostics-module-implementation.md`

## Summary

If you remember only five things about this module, remember these:

1. Diagnostics extends Clinical ordering; it does not replace it.
2. `DiagnosticFulfillment` is the operational center of gravity.
3. Files are a first-class result mode, not a fallback.
4. Templates exist to speed up staff work, not to burden it.
5. The module is designed to stay practical for small clinics while still leaving room to grow.
