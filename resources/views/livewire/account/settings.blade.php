<div>
    <section class="hero" style="padding: 3rem 0;">
        <div class="hero-content">
            <div class="container">
                <h1 class="h3 mb-0">Account Settings</h1>
            </div>
        </div>
    </section>

    <section class="section-sm">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    {{-- Profile --}}
                    <div class="card publication-card mb-4">
                        <div class="card-body p-4">
                            <h5 class="font-serif mb-3">Profile Information</h5>

                            @if($saved)
                                <div class="alert alert-success small py-2">Profile updated successfully.</div>
                            @endif

                            <form wire:submit="updateProfile">
                                <div class="mb-3">
                                    <label class="form-label small text-uppercase ls-wide">Name</label>
                                    <input type="text" class="form-control" wire:model="name">
                                    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-uppercase ls-wide">Email</label>
                                    <input type="email" class="form-control" wire:model="email">
                                    @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                @if(auth()->user()->google_id)
                                    <div class="alert alert-premium small py-2 mb-3">
                                        Connected with Google account.
                                    </div>
                                @endif

                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>

                    {{-- Password --}}
                    @if(auth()->user()->password)
                        <div class="card publication-card">
                            <div class="card-body p-4">
                                <h5 class="font-serif mb-3">Change Password</h5>

                                @if($passwordChanged)
                                    <div class="alert alert-success small py-2">Password changed successfully.</div>
                                @endif

                                <form wire:submit="updatePassword">
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Current Password</label>
                                        <input type="password" class="form-control" wire:model="current_password">
                                        @error('current_password') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">New Password</label>
                                        <input type="password" class="form-control" wire:model="new_password">
                                        @error('new_password') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Confirm New Password</label>
                                        <input type="password" class="form-control" wire:model="new_password_confirmation">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
