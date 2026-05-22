<?php

namespace App\Livewire\Admin\Seasons;

use App\Models\Season;
use App\Support\ContentDisk;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Season $season;

    public string $name = '';

    public int $year;

    public string $description = '';

    public $cover_image = null;

    public ?string $existing_cover = null;

    public string $subscription_price = '';

    public string $status = 'draft';

    public function mount(Season $season)
    {
        $this->season = $season;
        $this->name = $season->name;
        $this->year = $season->year;
        $this->description = $season->description ?? '';
        $this->existing_cover = $season->cover_image;
        $this->subscription_price = (string) $season->subscription_price;
        $this->status = $season->status;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:20480', // 20 MB
            'subscription_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,published,archived',
        ]);

        $disk = ContentDisk::name();
        $path = $this->existing_cover;
        if ($this->cover_image) {
            if ($this->existing_cover) {
                Storage::disk($disk)->delete($this->existing_cover);
            }
            $path = $this->cover_image->store('seasons', $disk);
        }

        $this->season->update([
            'name' => $this->name,
            'year' => $this->year,
            'description' => $this->description ?: null,
            'cover_image' => $path,
            'subscription_price' => $this->subscription_price,
            'status' => $this->status,
        ]);

        session()->flash('success', 'Season updated.');

        return redirect()->route('admin.seasons.index');
    }

    public function removeCover()
    {
        if ($this->existing_cover) {
            Storage::disk(ContentDisk::name())->delete($this->existing_cover);
            $this->season->update(['cover_image' => null]);
            $this->existing_cover = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.seasons.form', [
            'isEdit' => true,
        ])->layout('layouts.admin', ['title' => 'Edit Season']);
    }
}
