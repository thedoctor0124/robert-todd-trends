<?php

namespace App\Livewire\Admin\Publications;

use App\Models\Publication;
use App\Models\Season;
use App\Services\PreviewPdfService;
use App\Support\ContentDisk;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public int $season_id = 0;

    public string $title = '';

    public string $description = '';

    public $cover_image = null;

    public $pdf_file = null;

    public string $price = '';

    public int $sort_order = 0;

    public string $status = 'draft';

    public bool $is_digital_only = false;

    public bool $is_featured = false;

    public string $default_viewer_mode = 'flipbook';

    public function save(PreviewPdfService $previewPdfService)
    {
        $this->validate([
            'season_id' => 'required|exists:seasons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:20480', // 20 MB
            'pdf_file' => 'required|file|mimes:pdf|max:102400',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:draft,published',
            'is_digital_only' => 'boolean',
            'is_featured' => 'boolean',
            'default_viewer_mode' => 'required|in:flipbook,flat',
        ]);

        $coverPath = null;
        if ($this->cover_image) {
            $coverPath = $this->cover_image->store('publications/covers', ContentDisk::name());
        }

        $pdfPath = $this->pdf_file->store('publications/pdfs', ContentDisk::name());

        $publication = Publication::create([
            'season_id' => $this->season_id,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'cover_image' => $coverPath,
            'pdf_file' => $pdfPath,
            'price' => $this->price,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_digital_only' => $this->is_digital_only,
            'is_featured' => $this->is_featured,
            'default_viewer_mode' => $this->default_viewer_mode,
        ]);

        if ($previewPath = $previewPdfService->generate($publication, 5)) {
            $publication->update([
                'preview_pdf_file' => $previewPath,
                'page_count' => $previewPdfService->pageCount($publication),
            ]);
        }

        session()->flash('success', 'Publication created.');

        return redirect()->route('admin.publications.index');
    }

    public function render()
    {
        return view('livewire.admin.publications.form', [
            'isEdit' => false,
            'seasons' => Season::orderByDesc('year')->get(),
        ])->layout('layouts.admin', ['title' => 'Create Publication']);
    }
}
