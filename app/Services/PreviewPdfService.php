<?php

namespace App\Services;

use App\Models\Publication;
use App\Support\ContentDisk;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreviewPdfService
{
    public function pageCount(Publication $publication): ?int
    {
        if (! $publication->pdf_file) {
            return null;
        }

        $source = Storage::disk(ContentDisk::name())->path($publication->pdf_file);

        if (! is_file($source)) {
            return null;
        }

        $qpdf = trim((string) shell_exec('command -v qpdf 2>/dev/null'));

        if ($qpdf === '') {
            return null;
        }

        exec(escapeshellarg($qpdf).' --show-npages '.escapeshellarg($source).' 2>/dev/null', $output, $exitCode);

        if ($exitCode !== 0 || empty($output[0]) || ! is_numeric(trim($output[0]))) {
            return null;
        }

        return (int) trim($output[0]);
    }

    public function generate(Publication $publication, int $pages = 5): ?string
    {
        if (! $publication->pdf_file) {
            return null;
        }

        $disk = Storage::disk(ContentDisk::name());
        $source = $disk->path($publication->pdf_file);

        if (! is_file($source)) {
            return null;
        }

        $qpdf = trim((string) shell_exec('command -v qpdf 2>/dev/null'));
        if ($qpdf === '') {
            report(new \RuntimeException('qpdf is not installed; preview PDF could not be generated.'));

            return null;
        }

        $target = 'publications/previews/'.(Str::slug($publication->slug) ?: 'publication').'-preview.pdf';
        $targetPath = $disk->path($target);

        if (! is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0775, true);
        }

        $tmpPath = $targetPath.'.tmp-'.Str::random(8);
        $command = sprintf(
            '%s --empty --pages %s 1-%d -- %s 2>&1',
            escapeshellarg($qpdf),
            escapeshellarg($source),
            max(1, $pages),
            escapeshellarg($tmpPath),
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($tmpPath)) {
            @unlink($tmpPath);
            report(new \RuntimeException('qpdf preview generation failed: '.implode("\n", $output)));

            return null;
        }

        rename($tmpPath, $targetPath);
        @chmod($targetPath, 0664);

        return $target;
    }
}
