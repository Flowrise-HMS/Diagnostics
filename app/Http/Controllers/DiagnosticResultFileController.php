<?php

namespace Modules\Diagnostics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DiagnosticResultFileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Result files live on a private disk, so they are streamed here behind the
     * fulfillment policy rather than exposed under a public storage URL.
     */
    public function download(DiagnosticResultFile $resultFile): StreamedResponse
    {
        $fulfillment = $resultFile->fulfillment;

        if ($fulfillment === null) {
            throw new NotFoundHttpException('This result file is not linked to a fulfillment.');
        }

        $this->authorize('view', $fulfillment);

        $disk = Storage::disk(config('diagnostics.result_files.disk'));

        if (blank($resultFile->file_path) || ! $disk->exists($resultFile->file_path)) {
            throw new NotFoundHttpException('The stored result file is missing.');
        }

        return $disk->download($resultFile->file_path, $resultFile->file_name);
    }
}
