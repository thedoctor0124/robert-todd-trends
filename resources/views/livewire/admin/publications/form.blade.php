<div>
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.publications.index') }}" class="text-muted text-decoration-none me-3">&larr;</a>
        <h3 class="font-serif mb-0">{{ $isEdit ? 'Edit Publication' : 'New Publication' }}</h3>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06); max-width: 700px;">
        <form wire:submit="save">
            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Season</label>
                <select class="form-select" wire:model="season_id">
                    <option value="0">Select a season...</option>
                    @foreach($seasons as $season)
                        <option value="{{ $season->id }}">{{ $season->name }} ({{ $season->year }})</option>
                    @endforeach
                </select>
                @error('season_id') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Title</label>
                <input type="text" class="form-control" wire:model="title">
                @error('title') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Description</label>
                <textarea class="form-control" wire:model="description" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Cover Image</label>
                @if($isEdit && $existing_cover)
                    <div class="mb-2">
                        <img src="{{ $publication->cover_image_url }}" style="max-height: 120px;" class="d-block mb-1">
                        <button type="button" wire:click="removeCover" class="btn btn-sm btn-outline-danger">Remove</button>
                    </div>
                @endif
                <input type="file" class="form-control" wire:model="cover_image" accept="image/*">
                @error('cover_image') <span class="text-danger small">{{ $message }}</span> @enderror
                <div wire:loading wire:target="cover_image" class="text-muted small mt-1">Uploading...</div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">PDF File</label>
                @if($isEdit && $existing_pdf)
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <span class="badge-gold px-2 py-1">PDF uploaded</span>
                        <span class="text-muted small">{{ basename($existing_pdf) }}</span>
                    </div>
                @endif
                <input type="file" class="form-control" wire:model="pdf_file" accept="application/pdf">
                @error('pdf_file') <span class="text-danger small">{{ $message }}</span> @enderror
                <div wire:loading wire:target="pdf_file" class="text-muted small mt-1">Uploading PDF...</div>
                <div class="text-muted small mt-1">Max 100MB. Users can switch between flipbook and flat PDF views.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small text-uppercase ls-wide">Price (&pound;)</label>
                    <input type="number" step="0.01" class="form-control" wire:model="price">
                    @error('price') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-uppercase ls-wide">Sort Order</label>
                    <input type="number" class="form-control" wire:model="sort_order">
                    @error('sort_order') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-uppercase ls-wide">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-uppercase ls-wide">Default Viewer</label>
                <select class="form-select" wire:model="default_viewer_mode">
                    <option value="flipbook">Flipbook</option>
                    <option value="flat">Flat PDF</option>
                </select>
                <div class="text-muted small mt-1">
                    This controls which view opens first. Customers can still switch mode from the publication viewer.
                </div>
                @error('default_viewer_mode') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small text-uppercase ls-wide d-block">Format</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_digital_only" wire:model="is_digital_only">
                    <label class="form-check-label" for="is_digital_only">
                        Digital only (no printed magazine)
                    </label>
                </div>
                <div class="text-muted small mt-1">
                    Leave off if subscribers / buyers should also receive the printed magazine for this title.
                    Turn on for publications that are only ever delivered as a digital flipbook (no print run).
                </div>
                @error('is_digital_only') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small text-uppercase ls-wide d-block">Season Feature</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_featured" wire:model="is_featured">
                    <label class="form-check-label" for="is_featured">
                        Feature this publication on the season page
                    </label>
                </div>
                <div class="text-muted small mt-1">
                    Featured publications appear in a highlighted section above the standard season grid.
                    Use this for the key title or titles you want customers to notice first.
                </div>
                @error('is_featured') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Publication' : 'Create Publication' }}</button>
        </form>
    </div>
</div>
