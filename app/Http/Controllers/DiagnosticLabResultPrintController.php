<?php

namespace Modules\Diagnostics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Modules\Diagnostics\Classes\Services\DiagnosticLabResultPrintService;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DiagnosticLabResultPrintController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DiagnosticLabResultPrintService $printService
    ) {}

    public function show(DiagnosticFulfillment $fulfillment): View
    {
        $this->authorize('printLabResult', $fulfillment);

        if (! $this->printService->canPrint($fulfillment)) {
            throw new NotFoundHttpException('No printable lab results are available for this fulfillment.');
        }

        return view('diagnostics::prints.lab-result', $this->printService->build($fulfillment));
    }
}
