<div>
    <h3 class="font-serif mb-4">Users</h3>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <div class="mb-3">
            <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search by name or email...">
        </div>

        <table class="table table-minimal">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
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
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
