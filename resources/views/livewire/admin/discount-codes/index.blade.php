<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-serif mb-0">Discount Codes</h3>
        <a href="{{ route('admin.discount-codes.create') }}" class="btn btn-primary">New Code</a>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <table class="table table-minimal">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Applies To</th>
                    <th>Usage</th>
                    <th>Expires</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($codes as $code)
                    <tr>
                        <td><strong>{{ $code->code }}</strong></td>
                        <td>{{ ucfirst($code->type) }}</td>
                        <td>{{ $code->type === 'percentage' ? $code->value . '%' : '£' . number_format($code->value, 2) }}</td>
                        <td>
                            @if($code->season) Season: {{ $code->season->name }}
                            @elseif($code->publication) Pub: {{ $code->publication->title }}
                            @else All
                            @endif
                        </td>
                        <td>{{ $code->times_used }}{{ $code->usage_limit ? '/' . $code->usage_limit : '' }}</td>
                        <td>{{ $code->expires_at ? $code->expires_at->format('d M Y') : '—' }}</td>
                        <td>
                            <button wire:click="toggleActive({{ $code->id }})" class="btn btn-sm {{ $code->active ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                {{ $code->active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.discount-codes.edit', $code) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <button wire:click="delete({{ $code->id }})" wire:confirm="Delete this code?" class="btn btn-sm btn-outline-danger">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No discount codes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
