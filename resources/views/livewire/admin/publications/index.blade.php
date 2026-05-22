<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-serif mb-0">Publications</h3>
        <a href="{{ route('admin.publications.create') }}" class="btn btn-primary">New Publication</a>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <div class="mb-3">
            <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search publications...">
        </div>

        <table class="table table-minimal">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Season</th>
                    <th>Featured</th>
                    <th>Format</th>
                    <th>Price</th>
                    <th>Purchases</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($publications as $pub)
                    <tr>
                        <td><strong>{{ $pub->title }}</strong></td>
                        <td>{{ $pub->season->name }} ({{ $pub->season->year }})</td>
                        <td>
                            @if($pub->is_featured)
                                <span class="badge-gold">Featured</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            @if($pub->is_digital_only)
                                <span class="badge-navy">Digital only</span>
                            @else
                                <span class="badge-gold">Print + digital</span>
                            @endif
                        </td>
                        <td>&pound;{{ number_format($pub->price, 2) }}</td>
                        <td>{{ $pub->purchases_count }}</td>
                        <td>{{ $pub->sort_order }}</td>
                        <td>
                            <span class="badge-{{ $pub->status === 'published' ? 'gold' : 'navy' }}">
                                {{ ucfirst($pub->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.publications.edit', $pub) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <button wire:click="delete({{ $pub->id }})" wire:confirm="Delete this publication?" class="btn btn-sm btn-outline-danger">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No publications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
