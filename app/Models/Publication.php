<?php

namespace App\Models;

use App\Support\ContentDisk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Publication extends Model
{
    protected $fillable = [
        'season_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'pdf_file',
        'preview_pdf_file',
        'page_count',
        'price',
        'sort_order',
        'status',
        'is_digital_only',
        'is_featured',
        'default_viewer_mode',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'page_count' => 'integer',
        'is_digital_only' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Publication $pub) {
            if (empty($pub->slug)) {
                $pub->slug = Str::slug($pub->title);
            }
        });
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Whether this publication ships with a printed magazine in addition to the digital edition.
     */
    public function offersPrintedCopy(): bool
    {
        return ! $this->is_digital_only;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (! ContentDisk::isGoogle()) {
            return Storage::disk(ContentDisk::name())->url($this->cover_image);
        }

        $disk = Storage::disk('gcs');

        if ($disk->providesTemporaryUrls()) {
            try {
                return $disk->temporaryUrl($this->cover_image, now()->addDays(7));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $disk->url($this->cover_image);
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (! $this->pdf_file) {
            return null;
        }

        // Signed, root-relative URL so PDF.js in the Heyzine iframe loads same-origin (no GCS CORS) and
        // does not depend on the session cookie being sent from embedded/third-party contexts.
        return URL::temporarySignedRoute(
            'publications.stream-pdf',
            now()->addHours(4),
            ['slug' => $this->slug],
            absolute: false,
        );
    }

    public function getPreviewPdfUrlAttribute(): ?string
    {
        if (! $this->preview_pdf_file) {
            return null;
        }

        return URL::temporarySignedRoute(
            'publications.stream-preview-pdf',
            now()->addHours(12),
            ['slug' => $this->slug],
            absolute: false,
        );
    }

    public function getPreviousAttribute(): ?Publication
    {
        return static::where('season_id', $this->season_id)
            ->where('sort_order', '<', $this->sort_order)
            ->published()
            ->orderByDesc('sort_order')
            ->first();
    }

    public function getNextAttribute(): ?Publication
    {
        return static::where('season_id', $this->season_id)
            ->where('sort_order', '>', $this->sort_order)
            ->published()
            ->orderBy('sort_order')
            ->first();
    }
}
