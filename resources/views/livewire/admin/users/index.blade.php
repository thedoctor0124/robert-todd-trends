<div>
    <h3 class="font-serif mb-4">Users</h3>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <div class="row g-2 mb-3">
            <div class="col-md-7">
                <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                       placeholder="Search by name, email or company...">
            </div>
            <div class="col-md-5">
                <div class="d-flex gap-2">
                    <select class="form-select" wire:model.live="company">
                        <option value="" @selected($company === '')>All companies</option>
                        @foreach($companies as $companyName)
                            <option value="{{ $companyName }}" @selected($company === $companyName)>{{ $companyName }}</option>
                        @endforeach
                        @if($missingCompanyCount)
                            <option value="__none" @selected($company === '__none')>No company set ({{ $missingCompanyCount }})</option>
                        @endif
                    </select>
                    @if($search !== '' || $company !== '')
                        <button type="button" wire:click="clearFilters" class="btn btn-outline-primary flex-shrink-0">
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <table class="table table-minimal">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Purchases</th>
                    <th>Subscriptions</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if($user->google_id) <span class="text-muted small">(Google)</span> @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->company)
                                {{-- Not a .btn: the theme uppercases button text, and a company
                                     name must read exactly as it was entered. --}}
                                <button type="button" wire:click="$set('company', @js($user->company))"
                                        class="border-0 bg-transparent p-0 text-start"
                                        style="color: inherit; text-decoration: underline dotted; text-underline-offset: 3px; cursor: pointer;"
                                        title="Filter by {{ $user->company }}">
                                    {{ $user->company }}
                                </button>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>{{ $user->purchases_count }}</td>
                        <td>{{ $user->subscriptions_count }}</td>
                        <td>
                            @if($user->is_admin)
                                <span class="badge-gold">Admin</span>
                            @else
                                <span class="badge-navy">User</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
