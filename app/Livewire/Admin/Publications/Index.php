<?php

namespace App\Livewire\Admin\Publications;

use App\Models\Publication;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public function delete(int $id)
    {
        Publication::findOrFail($id)->delete();
        session()->flash('success', 'Publication deleted.');
    }

    public function render()
    {
        $query = Publication::with('season')->withCount('purchases');

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        return view('livewire.admin.publications.index', [
            'publications' => $query->orderByDesc('created_at')->get(),
        ])->layout('layouts.admin', ['title' => 'Publications']);
    }
}
