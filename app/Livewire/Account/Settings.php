<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Settings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public bool $saved = false;

    public bool $passwordChanged = false;

    public function mount()
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.auth()->id(),
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->saved = true;
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (! Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'Current password is incorrect.');

            return;
        }

        auth()->user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->passwordChanged = true;
    }

    public function render()
    {
        return view('livewire.account.settings')->layout('layouts.app', ['title' => 'Account Settings']);
    }
}
