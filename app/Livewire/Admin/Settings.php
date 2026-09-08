<?php

namespace App\Livewire\Admin;

use App\Models\AppSetting;
use App\Services\MailSettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Settings extends Component
{
    public string $mailHost = '';

    public int $mailPort = 587;

    public string $mailEncryption = 'tls';

    public string $mailUsername = '';

    /**
     * Write-only. The stored password is never sent to the browser; leaving this
     * blank on save keeps whatever is already stored.
     */
    public string $mailPassword = '';

    public string $mailFromAddress = '';

    public string $mailFromName = '';

    public bool $passwordIsSet = false;

    public string $testRecipient = '';

    /**
     * Outcome of the last test send, shown inline beside the button. SMTP errors
     * are verbose, so they read better next to the control than in a page-top
     * flash message.
     */
    public ?string $testStatus = null;

    public string $testMessage = '';

    public function mount(): void
    {
        $this->mailHost = MailSettings::host();
        $this->mailPort = MailSettings::port();
        $this->mailEncryption = MailSettings::encryption();
        $this->mailUsername = MailSettings::username();
        $this->mailFromAddress = AppSetting::string(AppSetting::MAIL_FROM_ADDRESS);
        $this->mailFromName = MailSettings::fromName();
        $this->passwordIsSet = MailSettings::passwordIsSet();
        $this->testRecipient = (string) auth()->user()->email;
    }

    protected function rules(): array
    {
        return [
            'mailHost' => 'required|string|max:255',
            'mailPort' => 'required|integer|min:1|max:65535',
            'mailEncryption' => 'required|in:tls,ssl',
            'mailUsername' => 'required|string|max:255',
            // Only mandatory the first time — a blank field means "leave as is".
            'mailPassword' => $this->passwordIsSet ? 'nullable|string|max:255' : 'required|string|max:255',
            'mailFromAddress' => 'nullable|email|max:255',
            'mailFromName' => 'required|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'mailPassword.required' => 'Enter the 16-character app password generated in your Google Account.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        AppSetting::setString(AppSetting::MAIL_HOST, trim($this->mailHost));
        AppSetting::setString(AppSetting::MAIL_PORT, (string) $this->mailPort);
        AppSetting::setString(AppSetting::MAIL_ENCRYPTION, $this->mailEncryption);
        AppSetting::setString(AppSetting::MAIL_USERNAME, trim($this->mailUsername));
        AppSetting::setString(AppSetting::MAIL_FROM_ADDRESS, trim($this->mailFromAddress));
        AppSetting::setString(AppSetting::MAIL_FROM_NAME, trim($this->mailFromName));

        if ($this->mailPassword !== '') {
            // Google presents app passwords as four space-separated groups; SMTP
            // wants the bare 16 characters.
            AppSetting::setEncrypted(
                AppSetting::MAIL_PASSWORD,
                preg_replace('/\s+/', '', $this->mailPassword) ?? '',
            );
        }

        $this->mailPassword = '';
        MailSettings::refresh();
        $this->passwordIsSet = MailSettings::passwordIsSet();

        session()->flash('success', 'Email settings saved.');
    }

    public function clearPassword(): void
    {
        AppSetting::setEncrypted(AppSetting::MAIL_PASSWORD, '');

        $this->mailPassword = '';
        MailSettings::refresh();
        $this->passwordIsSet = false;

        session()->flash('success', 'Stored app password cleared. Email sending is now disabled until a new one is saved.');
    }

    public function sendTest(): void
    {
        $this->testStatus = null;
        $this->testMessage = '';

        $this->validateOnly('testRecipient', ['testRecipient' => 'required|email|max:255']);

        if (! MailSettings::configured()) {
            $this->testStatus = 'error';
            $this->testMessage = 'Save a host, username and app password before sending a test.';

            return;
        }

        MailSettings::refresh();

        try {
            Mail::raw(
                'This is a test message from '.config('app.name').".\n\n"
                ."If you received it, SMTP is configured correctly.\n"
                .'Sent from: '.MailSettings::fromAddress()."\n"
                .'Server: '.MailSettings::host().':'.MailSettings::port().' ('.strtoupper(MailSettings::encryption()).')',
                fn ($message) => $message->to($this->testRecipient)->subject(config('app.name').' — SMTP test'),
            );
        } catch (\Throwable $e) {
            // Surface the transport error verbatim; Gmail's auth failures are the
            // fastest way to tell a bad app password from 2FA not being enabled.
            $this->testStatus = 'error';
            $this->testMessage = $e->getMessage();

            return;
        }

        $this->testStatus = 'success';
        $this->testMessage = 'Test email sent to '.$this->testRecipient.'.';
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'effectiveFromAddress' => MailSettings::fromAddress(),
            'mailConfigured' => MailSettings::configured(),
        ])->layout('layouts.admin', ['title' => 'Email Settings']);
    }
}
