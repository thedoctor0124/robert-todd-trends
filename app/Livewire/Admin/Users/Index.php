<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /** Sentinel for "no company recorded", since '' already means "no filter". */
    public const NO_COMPANY = '__none';

    public string $search = '';

    /**
     * Exact company to show, or the sentinel '__none' for users with no company
     * recorded. Empty means no filtering.
     */
    public string $company = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'company' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCompany()
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->company = '';
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
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('company', 'like', "%{$this->search}%");
            });
        }

        if ($this->company === self::NO_COMPANY) {
            $query->where(function ($q) {
                $q->whereNull('company')->orWhere('company', '');
            });
        } elseif ($this->company !== '') {
            $query->where('company', $this->company);
        }

        return view('livewire.admin.users.index', [
            'users' => $query->orderByDesc('created_at')->paginate(25),
            'companies' => User::query()
                ->whereNotNull('company')
                ->where('company', '!=', '')
                ->distinct()
                ->orderBy('company')
                ->pluck('company'),
            'missingCompanyCount' => User::query()
                ->where(fn ($q) => $q->whereNull('company')->orWhere('company', ''))
                ->count(),
        ])->layout('layouts.admin', ['title' => 'Users']);
    }
}
