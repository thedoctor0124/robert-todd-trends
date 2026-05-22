<?php

namespace App\Console\Commands;

use App\Models\Publication;
use App\Services\PreviewPdfService;
use Illuminate\Console\Command;

class GeneratePublicationPreviews extends Command
{
    protected $signature = 'publications:generate-previews {--force : Regenerate existing previews}';

    protected $description = 'Generate first-five-page preview PDFs for publications';

    public function handle(PreviewPdfService $previewPdfService): int
    {
        $query = Publication::query()->whereNotNull('pdf_file');

        if (! $this->option('force')) {
            $query->whereNull('preview_pdf_file');
        }

        $count = 0;

        foreach ($query->orderBy('id')->cursor() as $publication) {
            $path = $previewPdfService->generate($publication, 5);
            $pageCount = $previewPdfService->pageCount($publication);

            if ($path) {
                $publication->update([
                    'preview_pdf_file' => $path,
                    'page_count' => $pageCount,
                ]);
                $count++;
                $this->line("Generated preview for {$publication->title}");
            } else {
                $this->warn("Skipped {$publication->title}");
            }
        }

        $this->info("Generated {$count} preview PDFs.");

        return self::SUCCESS;
    }
}
