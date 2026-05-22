<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleAdmin(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_admin' => ! $user->is_admin]);
    }

    public function render()
    {
        $query = User::withCount('purchases', 'subscriptions');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return view('livewire.admin.users.index', [
            'users' => $query->orderByDesc('created_at')->paginate(25),
        ])->layout('layouts.admin', ['title' => 'Users']);
    }
}
