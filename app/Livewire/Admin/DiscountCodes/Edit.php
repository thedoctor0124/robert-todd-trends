<?php

namespace App\Livewire\Admin\DiscountCodes;

use App\Models\DiscountCode;
use App\Models\Publication;
use App\Models\Season;
use Livewire\Component;

class Edit extends Component
{
    public DiscountCode $discountCode;

    public string $code = '';

    public string $type = 'percentage';

    public string $value = '';

    public ?int $season_id = null;

    public ?int $publication_id = null;

    public ?int $usage_limit = null;

    public ?string $expires_at = null;

    public bool $active = true;

    public function mount(DiscountCode $discountCode)
    {
        $this->discountCode = $discountCode;
        $this->code = $discountCode->code;
        $this->type = $discountCode->type;
        $this->value = (string) $discountCode->value;
        $this->season_id = $discountCode->season_id;
        $this->publication_id = $discountCode->publication_id;
        $this->usage_limit = $discountCode->usage_limit;
        $this->expires_at = $discountCode->expires_at?->format('Y-m-d');
        $this->active = $discountCode->active;
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|string|unique:discount_codes,code,'.$this->discountCode->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'season_id' => 'nullable|exists:seasons,id',
            'publication_id' => 'nullable|exists:publications,id',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $this->discountCode->update([
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value' => $this->value,
            'season_id' => $this->season_id,
            'publication_id' => $this->publication_id,
            'usage_limit' => $this->usage_limit,
            'expires_at' => $this->expires_at,
            'active' => $this->active,
        ]);

        session()->flash('success', 'Discount code updated.');

        return redirect()->route('admin.discount-codes.index');
    }

    public function render()
    {
        return view('livewire.admin.discount-codes.form', [
            'isEdit' => true,
            'seasons' => Season::orderByDesc('year')->get(),
            'publications' => Publication::with('season')->orderBy('title')->get(),
        ])->layout('layouts.admin', ['title' => 'Edit Discount Code']);
    }
}
