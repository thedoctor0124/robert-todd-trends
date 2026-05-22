<div>
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.seasons.index') }}" class="text-muted text-decoration-none me-3">&larr;</a>
        <h3 class="font-serif mb-0">{{ $isEdit ? 'Edit Season' : 'New Season' }}</h3>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06); max-width: 700px;">
        <form wire:submit="save">
            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Season Name</label>
                <input type="text" class="form-control" wire:model="name" placeholder="e.g. Autumn/Winter">
                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Year</label>
                    <input type="number" class="form-control" wire:model="year">
                    @error('year') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Subscription Price (&pound;)</label>
                    <input type="number" step="0.01" class="form-control" wire:model="subscription_price">
                    @error('subscription_price') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Description</label>
                <textarea class="form-control" wire:model="description" rows="3"></textarea>
                @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Cover Image</label>
                @if($isEdit && $existing_cover)
                    <div class="mb-2">
                        <img src="{{ $season->cover_image_url }}" style="max-height: 120px;" class="d-block mb-1">
                        <button type="button" wire:click="removeCover" class="btn btn-sm btn-outline-danger">Remove</button>
                    </div>
                @endif
                <input type="file" class="form-control" wire:model="cover_image" accept="image/*">
                @error('cover_image') <span class="text-danger small">{{ $message }}</span> @enderror
                <div wire:loading wire:target="cover_image" class="text-muted small mt-1">Uploading...</div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-uppercase ls-wide">Status</label>
                <select class="form-select" wire:model="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Season' : 'Create Season' }}</button>
        </form>
    </div>
</div>
