<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->create([
            'email' => 'admin@roberttodds.com',
            'is_admin' => true,
        ]);
    }

    public function test_settings_page_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Email Settings')
            ->assertSee('Not configured');
    }

    public function test_settings_page_is_closed_to_non_admins(): void
    {
        $user = User::factory()->create(['email' => 'someone@example.com']);

        $this->actingAs($user)
            ->get(route('admin.settings'))
            ->assertForbidden();
    }

    public function test_saving_credentials_configures_the_mailer(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->set('mailHost', 'smtp.gmail.com')
            ->set('mailPort', 587)
            ->set('mailEncryption', 'tls')
            ->set('mailUsername', 'sender@gmail.com')
            // Google shows app passwords in four space-separated groups.
            ->set('mailPassword', 'abcd efgh ijkl mnop')
            ->set('mailFromAddress', 'noreply@roberttodds.com')
            ->set('mailFromName', 'Robert Todd Trends')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('mailPassword', '')
            ->assertSet('passwordIsSet', true);

        $this->assertSame('abcdefghijklmnop', MailSettings::password());
        $this->assertTrue(MailSettings::configured());
        $this->assertSame('noreply@roberttodds.com', MailSettings::fromAddress());

        MailSettings::apply();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertSame('sender@gmail.com', config('mail.mailers.smtp.username'));
        $this->assertSame('abcdefghijklmnop', config('mail.mailers.smtp.password'));
        $this->assertSame('noreply@roberttodds.com', config('mail.from.address'));
        $this->assertSame('Robert Todd Trends', config('mail.from.name'));
    }

    public function test_password_is_stored_encrypted_and_never_exposed_to_the_browser(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->set('mailUsername', 'sender@gmail.com')
            ->set('mailPassword', 'abcdefghijklmnop')
            ->call('save')
            ->assertHasNoErrors();

        $stored = AppSetting::query()->where('key', AppSetting::MAIL_PASSWORD)->value('value');
        $this->assertNotSame('abcdefghijklmnop', $stored);
        $this->assertStringNotContainsString('abcdefghijklmnop', $stored);

        // Remounting must not leak the stored secret into the rendered component.
        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->assertSet('mailPassword', '')
            ->assertSet('passwordIsSet', true)
            ->assertDontSee('abcdefghijklmnop');
    }

    public function test_blank_password_on_save_keeps_the_existing_one(): void
    {
        AppSetting::setString(AppSetting::MAIL_USERNAME, 'sender@gmail.com');
        AppSetting::setEncrypted(AppSetting::MAIL_PASSWORD, 'abcdefghijklmnop');

        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->set('mailFromName', 'Renamed Sender')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('abcdefghijklmnop', MailSettings::password());
        $this->assertSame('Renamed Sender', MailSettings::fromName());
    }

    public function test_password_is_required_on_first_save(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->set('mailUsername', 'sender@gmail.com')
            ->set('mailPassword', '')
            ->call('save')
            ->assertHasErrors(['mailPassword' => 'required']);
    }

    public function test_from_address_falls_back_to_the_username(): void
    {
        AppSetting::setString(AppSetting::MAIL_USERNAME, 'sender@gmail.com');
        AppSetting::setString(AppSetting::MAIL_FROM_ADDRESS, '');

        $this->assertSame('sender@gmail.com', MailSettings::fromAddress());
    }

    public function test_ssl_selection_uses_the_implicit_tls_scheme(): void
    {
        AppSetting::setString(AppSetting::MAIL_HOST, 'smtp.gmail.com');
        AppSetting::setString(AppSetting::MAIL_USERNAME, 'sender@gmail.com');
        AppSetting::setEncrypted(AppSetting::MAIL_PASSWORD, 'abcdefghijklmnop');
        AppSetting::setString(AppSetting::MAIL_PORT, '465');
        AppSetting::setString(AppSetting::MAIL_ENCRYPTION, 'ssl');

        MailSettings::apply();

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
    }

    public function test_incomplete_settings_leave_the_environment_config_alone(): void
    {
        AppSetting::setString(AppSetting::MAIL_HOST, 'smtp.gmail.com');
        AppSetting::setString(AppSetting::MAIL_USERNAME, 'sender@gmail.com');
        // No password stored.

        $before = config('mail.default');
        MailSettings::apply();

        $this->assertFalse(MailSettings::configured());
        $this->assertSame($before, config('mail.default'));
    }

    public function test_clearing_the_password_disables_sending(): void
    {
        AppSetting::setString(AppSetting::MAIL_USERNAME, 'sender@gmail.com');
        AppSetting::setEncrypted(AppSetting::MAIL_PASSWORD, 'abcdefghijklmnop');

        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->call('clearPassword')
            ->assertSet('passwordIsSet', false);

        $this->assertSame('', MailSettings::password());
        $this->assertFalse(MailSettings::configured());
    }

    /**
     * The credentials are pushed into the config by a resolving hook on the mail
     * manager, so assert the transport a fresh resolve actually produces.
     */
    public function test_resolving_the_mailer_picks_up_the_stored_credentials(): void
    {
        AppSetting::setString(AppSetting::MAIL_HOST, 'smtp.gmail.com');
        AppSetting::setString(AppSetting::MAIL_PORT, '587');
        AppSetting::setString(AppSetting::MAIL_ENCRYPTION, 'tls');
        AppSetting::setString(AppSetting::MAIL_USERNAME, 'sender@gmail.com');
        AppSetting::setEncrypted(AppSetting::MAIL_PASSWORD, 'abcdefghijklmnop');

        // The testing environment starts on the array mailer.
        $this->assertSame('array', config('mail.default'));

        $this->app->forgetInstance('mailer');
        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstance();

        $transport = Mail::mailer('smtp')->getSymfonyTransport();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp://smtp.gmail.com:587', (string) $transport);
    }

    public function test_test_send_is_refused_until_credentials_are_saved(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Settings::class)
            ->set('testRecipient', 'admin@roberttodds.com')
            ->call('sendTest')
            ->assertSet('testStatus', 'error')
            ->assertSet('testMessage', 'Save a host, username and app password before sending a test.');
    }
}
