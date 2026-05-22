<?php

namespace App\Livewire\Admin\DiscountCodes;

use App\Models\DiscountCode;
use App\Models\Publication;
use App\Models\Season;
use Livewire\Component;

class Create extends Component
{
    public string $code = '';

    public string $type = 'percentage';

    public string $value = '';

    public ?int $season_id = null;

    public ?int $publication_id = null;

    public ?int $usage_limit = null;

    public ?string $expires_at = null;

    public bool $active = true;

    public function save()
    {
        $this->validate([
            'code' => 'required|string|unique:discount_codes,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'season_id' => 'nullable|exists:seasons,id',
            'publication_id' => 'nullable|exists:publications,id',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
        ]);

        DiscountCode::create([
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value' => $this->value,
            'season_id' => $this->season_id,
            'publication_id' => $this->publication_id,
            'usage_limit' => $this->usage_limit,
            'expires_at' => $this->expires_at,
            'active' => $this->active,
        ]);

        session()->flash('success', 'Discount code created.');

        return redirect()->route('admin.discount-codes.index');
    }

    public function render()
    {
        return view('livewire.admin.discount-codes.form', [
            'isEdit' => false,
            'seasons' => Season::orderByDesc('year')->get(),
            'publications' => Publication::with('season')->orderBy('title')->get(),
        ])->layout('layouts.admin', ['title' => 'Create Discount Code']);
    }
}
