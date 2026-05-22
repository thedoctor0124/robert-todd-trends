<?php

namespace App\Livewire\Admin\Seasons;

use App\Models\Season;
use App\Support\ContentDisk;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $name = '';

    public int $year;

    public string $description = '';

    public $cover_image = null;

    public string $subscription_price = '';

    public string $status = 'draft';

    public function mount()
    {
        $this->year = (int) date('Y');
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

        $path = null;
        if ($this->cover_image) {
            $path = $this->cover_image->store('seasons', ContentDisk::name());
        }

        Season::create([
            'name' => $this->name,
            'year' => $this->year,
            'description' => $this->description ?: null,
            'cover_image' => $path,
            'subscription_price' => $this->subscription_price,
            'status' => $this->status,
        ]);

        session()->flash('success', 'Season created.');

        return redirect()->route('admin.seasons.index');
    }

    public function render()
    {
        return view('livewire.admin.seasons.form', [
            'isEdit' => false,
        ])->layout('layouts.admin', ['title' => 'Create Season']);
    }
}
