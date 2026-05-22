<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-serif mb-0">Seasons</h3>
        <a href="{{ route('admin.seasons.create') }}" class="btn btn-primary">New Season</a>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <table class="table table-minimal">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Year</th>
                    <th>Publications</th>
                    <th>Subscriptions</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($seasons as $season)
                    <tr>
                        <td><strong>{{ $season->name }}</strong></td>
                        <td>{{ $season->year }}</td>
                        <td>{{ $season->publications_count }}</td>
                        <td>{{ $season->subscriptions_count }}</td>
                        <td>&pound;{{ number_format($season->subscription_price, 2) }}</td>
                        <td>
                            <span class="badge-{{ $season->status === 'published' ? 'gold' : 'navy' }}">
                                {{ ucfirst($season->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.seasons.edit', $season) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <button wire:click="delete({{ $season->id }})" wire:confirm="Delete this season and all its publications?" class="btn btn-sm btn-outline-danger">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No seasons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
