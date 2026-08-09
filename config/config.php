<?php

return [
    'name' => 'Diagnostics',

    /*
     * Result files contain patient-identifiable clinical data, so they are kept on a
     * private disk and served only through signed, policy-authorized download links.
     */
    'result_files' => [
        'disk' => env('DIAGNOSTICS_RESULT_FILES_DISK', 'local'),
        'directory' => 'diagnostics/results',
        'link_ttl_minutes' => 5,
    ],

    'permissions' => [
        'assign_diagnostic_fulfillment' => 'Assign Diagnostic Fulfillment',
        'collect_diagnostic_specimen' => 'Collect Diagnostic Specimen',
        'upload_diagnostic_result_file' => 'Upload Diagnostic Result File',
        'finalize_diagnostic_result' => 'Finalize Diagnostic Result',
        'verify_diagnostic_result' => 'Verify Diagnostic Result',
        'sign_diagnostic_report' => 'Sign Diagnostic Report',
        'amend_diagnostic_report' => 'Amend Diagnostic Report',
        'manage_diagnostic_panels' => 'Manage Diagnostic Panels',
        'manage_diagnostic_reference_ranges' => 'Manage Diagnostic Reference Ranges',
        'record_structured_diagnostic_observations' => 'Record Structured Diagnostic Observations',
        'manage_diagnostic_allocations' => 'Manage Diagnostic Allocations',
        'manage_diagnostic_specimen_processing' => 'Manage Diagnostic Specimen Processing',
        'print_diagnostic_lab_result' => 'Print Diagnostic Lab Result',
    ],
];
