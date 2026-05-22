<div>
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.discount-codes.index') }}" class="text-muted text-decoration-none me-3">&larr;</a>
        <h3 class="font-serif mb-0">{{ $isEdit ? 'Edit Discount Code' : 'New Discount Code' }}</h3>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06); max-width: 600px;">
        <form wire:submit="save">
            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Code</label>
                <input type="text" class="form-control" wire:model="code" style="text-transform: uppercase;" placeholder="e.g. SUMMER20">
                @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Type</label>
                    <select class="form-select" wire:model="type">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount (&pound;)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Value</label>
                    <input type="number" step="0.01" class="form-control" wire:model="value">
                    @error('value') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Restrict to Season (optional)</label>
                <select class="form-select" wire:model="season_id">
                    <option value="">All seasons</option>
                    @foreach($seasons as $season)
                        <option value="{{ $season->id }}">{{ $season->name }} ({{ $season->year }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Restrict to Publication (optional)</label>
                <select class="form-select" wire:model="publication_id">
                    <option value="">All publications</option>
                    @foreach($publications as $pub)
                        <option value="{{ $pub->id }}">{{ $pub->title }} ({{ $pub->season->name }})</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Usage Limit (optional)</label>
                    <input type="number" class="form-control" wire:model="usage_limit" placeholder="Unlimited">
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Expires (optional)</label>
                    <input type="date" class="form-control" wire:model="expires_at">
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" wire:model="active" id="active">
                <label class="form-check-label" for="active">Active</label>
            </div>

            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Code' : 'Create Code' }}</button>
        </form>
    </div>
</div>
