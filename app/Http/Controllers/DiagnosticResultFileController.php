<?php

namespace Modules\Diagnostics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
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
        $this->authorizeStoredFile($resultFile);

        return $this->disk()->download($resultFile->file_path, $resultFile->file_name);
    }

    /**
     * Same file, served inline so images and PDFs open in the browser instead of
     * being saved to disk.
     */
    public function inline(DiagnosticResultFile $resultFile): StreamedResponse
    {
        $this->authorizeStoredFile($resultFile);

        $disk = $this->disk();
        $headers = [];

        if (filled($resultFile->mime_type)) {
            $headers['Content-Type'] = $resultFile->mime_type;
        }

        return $disk->response($resultFile->file_path, $resultFile->file_name, $headers);
    }

    protected function authorizeStoredFile(DiagnosticResultFile $resultFile): void
    {
        $fulfillment = $resultFile->fulfillment;

        if ($fulfillment === null) {
            throw new NotFoundHttpException('This result file is not linked to a fulfillment.');
        }

        $this->authorize('view', $fulfillment);

        if (blank($resultFile->file_path) || ! $this->disk()->exists($resultFile->file_path)) {
            throw new NotFoundHttpException('The stored result file is missing.');
        }
    }

    protected function disk(): Filesystem
    {
        return Storage::disk(config('diagnostics.result_files.disk'));
    }
}
