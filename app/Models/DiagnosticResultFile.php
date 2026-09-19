<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticResultFileFactory;
use Modules\Diagnostics\Enums\FileSourceType;

/**
 * @property string|null $file_name
 * @property string|null $file_path
 * @property string|null $file_type
 * @property string|null $mime_type
 * @property int|null $file_size
 *
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticResultFile extends BaseModel
{
    /** @use HasFactory<DiagnosticResultFileFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'report_version_id',
        'file_type',
        'source',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'checksum',
        'is_authoritative',
        'uploaded_by',
        'notes',
    ];

    protected $casts = [
        'source' => FileSourceType::class,
        'file_size' => 'integer',
        'is_authoritative' => 'boolean',
    ];

    protected static function newFactory(): DiagnosticResultFileFactory
    {
        return DiagnosticResultFileFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(DiagnosticReportVersion::class, 'report_version_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Short-lived signed link to the private download route. Returns null when no file
     * was stored, so callers can render the row without a broken link.
     */
    public function downloadUrl(): ?string
    {
        return $this->signedUrl('diagnostics.result-files.download');
    }

    /**
     * Signed link that opens the file in the browser (images, PDFs).
     */
    public function inlineUrl(): ?string
    {
        return $this->signedUrl('diagnostics.result-files.inline');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/')
            || in_array(strtolower((string) $this->file_type), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf' || strtolower((string) $this->file_type) === 'pdf';
    }

    public function canOpenInline(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    protected function signedUrl(string $routeName): ?string
    {
        if (blank($this->file_path)) {
            return null;
        }

        return URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes((int) config('diagnostics.result_files.link_ttl_minutes', 5)),
            ['resultFile' => $this->getKey()],
        );
    }
}
