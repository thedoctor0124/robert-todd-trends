<?php

namespace App\Livewire\Admin\Publications;

use App\Models\Publication;
use App\Models\Season;
use App\Services\PreviewPdfService;
use App\Support\ContentDisk;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Publication $publication;

    public int $season_id = 0;

    public string $title = '';

    public string $description = '';

    public $cover_image = null;

    public ?string $existing_cover = null;

    public $pdf_file = null;

    public ?string $existing_pdf = null;

    public string $price = '';

    public int $sort_order = 0;

    public string $status = 'draft';

    public bool $is_digital_only = false;

    public bool $is_featured = false;

    public string $default_viewer_mode = 'flipbook';

    public function mount(Publication $publication)
    {
        $this->publication = $publication;
        $this->season_id = $publication->season_id;
        $this->title = $publication->title;
        $this->description = $publication->description ?? '';
        $this->existing_cover = $publication->cover_image;
        $this->existing_pdf = $publication->pdf_file;
        $this->price = (string) $publication->price;
        $this->sort_order = $publication->sort_order;
        $this->status = $publication->status;
        $this->is_digital_only = (bool) $publication->is_digital_only;
        $this->is_featured = (bool) $publication->is_featured;
        $this->default_viewer_mode = $publication->default_viewer_mode === 'flat' ? 'flat' : 'flipbook';
    }

    public function save(PreviewPdfService $previewPdfService)
    {
        $rules = [
            'season_id' => 'required|exists:seasons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:20480', // 20 MB
            'pdf_file' => 'nullable|file|mimes:pdf|max:102400',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:draft,published',
            'is_digital_only' => 'boolean',
            'is_featured' => 'boolean',
            'default_viewer_mode' => 'required|in:flipbook,flat',
        ];

        if (! $this->existing_pdf) {
            $rules['pdf_file'] = 'required|file|mimes:pdf|max:102400';
        }

        $this->validate($rules);

        $disk = ContentDisk::name();

        $coverPath = $this->existing_cover;
        if ($this->cover_image) {
            if ($this->existing_cover) {
                Storage::disk($disk)->delete($this->existing_cover);
            }
            $coverPath = $this->cover_image->store('publications/covers', $disk);
        }

        $pdfPath = $this->existing_pdf;
        $previewPdfPath = $this->publication->preview_pdf_file;
        if ($this->pdf_file) {
            if ($this->existing_pdf) {
                Storage::disk($disk)->delete($this->existing_pdf);
            }
            if ($previewPdfPath) {
                Storage::disk($disk)->delete($previewPdfPath);
                $previewPdfPath = null;
            }
            $pdfPath = $this->pdf_file->store('publications/pdfs', $disk);
        }

        $this->publication->update([
            'season_id' => $this->season_id,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'cover_image' => $coverPath,
            'pdf_file' => $pdfPath,
            'preview_pdf_file' => $previewPdfPath,
            'page_count' => $this->publication->page_count,
            'price' => $this->price,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_digital_only' => $this->is_digital_only,
            'is_featured' => $this->is_featured,
            'default_viewer_mode' => $this->default_viewer_mode,
        ]);

        if ($this->pdf_file && ($previewPath = $previewPdfService->generate($this->publication->refresh(), 5))) {
            $this->publication->update([
                'preview_pdf_file' => $previewPath,
                'page_count' => $previewPdfService->pageCount($this->publication),
            ]);
        }

        session()->flash('success', 'Publication updated.');

        return redirect()->route('admin.publications.index');
    }

    public function removeCover()
    {
        if ($this->existing_cover) {
            Storage::disk(ContentDisk::name())->delete($this->existing_cover);
            $this->publication->update(['cover_image' => null]);
            $this->existing_cover = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.publications.form', [
            'isEdit' => true,
            'seasons' => Season::orderByDesc('year')->get(),
        ])->layout('layouts.admin', ['title' => 'Edit Publication']);
    }
}
