<?php

namespace App\Livewire\Admin\DiscountCodes;

use App\Models\DiscountCode;
use Livewire\Component;

class Index extends Component
{
    public function toggleActive(int $id)
    {
        $code = DiscountCode::findOrFail($id);
        $code->update(['active' => ! $code->active]);
    }

    public function delete(int $id)
    {
        DiscountCode::findOrFail($id)->delete();
        session()->flash('success', 'Discount code deleted.');
    }

    public function render()
    {
        return view('livewire.admin.discount-codes.index', [
            'codes' => DiscountCode::with('season', 'publication')->orderByDesc('created_at')->get(),
        ])->layout('layouts.admin', ['title' => 'Discount Codes']);
    }
}
