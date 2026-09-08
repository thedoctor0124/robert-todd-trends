<?php

namespace Tests\Feature;

use App\Livewire\Admin\SendAccess;
use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use App\Services\AccessInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AccessInviteDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const SMTP_ERROR = 'Failed to authenticate on SMTP server: 535 5.7.8 BadCredentials';

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->create([
            'email' => 'admin@roberttodds.com',
            'is_admin' => true,
        ]);
    }

    private function publication(): Publication
    {
        $season = Season::create([
            'name' => 'Autumn/Winter',
            'slug' => 'autumn-winter-2026',
            'year' => 2026,
            'subscription_price' => 500,
            'status' => 'published',
        ]);

        return Publication::create([
            'season_id' => $season->id,
            'title' => 'Design Direction',
            'slug' => 'design-direction',
            'price' => 150,
            'status' => 'published',
        ]);
    }

    private function invite(array $overrides = []): AccessInvite
    {
        return AccessInvite::create(array_merge([
            'token' => 'tok'.str_repeat('a', 20).uniqid(),
            'email' => 'recipient@example.com',
            'access_type' => 'publication',
            'publication_id' => $this->publication()->id,
            'granted_by' => 'admin@roberttodds.com',
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }

    /** Make the transport blow up the way Gmail did with bad credentials. */
    private function failSending(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException(self::SMTP_ERROR));
    }

    public function test_a_successful_send_is_recorded_on_the_invite(): void
    {
        Mail::fake();

        $invite = app(AccessInviteService::class)->createAndSend(
            email: 'recipient@example.com',
            accessType: 'publication',
            itemId: $this->publication()->id,
            grantedBy: 'admin@roberttodds.com',
        );

        $this->assertNotNull($invite->sent_at);
        $this->assertNull($invite->send_failed_at);
        $this->assertNull($invite->send_error);
        $this->assertTrue($invite->wasDelivered());
        $this->assertFalse($invite->deliveryFailed());
    }

    public function test_a_failed_send_is_recorded_instead_of_being_swallowed(): void
    {
        $this->failSending();

        $invite = app(AccessInviteService::class)->createAndSend(
            email: 'recipient@example.com',
            accessType: 'publication',
            itemId: $this->publication()->id,
            grantedBy: 'admin@roberttodds.com',
        );

        // The invite must survive: its claim URL is the fallback when mail is down.
        $this->assertTrue($invite->exists);
        $this->assertNull($invite->sent_at);
        $this->assertNotNull($invite->send_failed_at);
        $this->assertStringContainsString('BadCredentials', $invite->send_error);
        $this->assertTrue($invite->deliveryFailed());
        $this->assertTrue($invite->needsResend());
    }

    public function test_the_admin_is_told_when_the_email_did_not_go_out(): void
    {
        $this->failSending();
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'new')
            ->set('invitedName', 'May Hawkins')
            ->set('email', 'may@example.com')
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors()
            ->assertSet('feedbackStatus', 'error')
            ->assertSet('feedbackMessage', fn (string $m) => str_contains($m, 'could NOT be sent')
                && str_contains($m, 'BadCredentials'))
            ->assertSee('Email not sent');
    }

    public function test_a_successful_send_reports_success(): void
    {
        Mail::fake();
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'new')
            ->set('invitedName', 'May Hawkins')
            ->set('email', 'may@example.com')
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors()
            ->assertSet('feedbackStatus', 'success')
            ->assertSet('feedbackMessage', fn (string $m) => str_contains($m, 'emailed to may@example.com'))
            ->assertDontSee('Email not sent');
    }

    public function test_resending_clears_a_previous_failure(): void
    {
        $invite = $this->invite([
            'send_failed_at' => now()->subHour(),
            'send_error' => self::SMTP_ERROR,
        ]);
        $this->assertTrue($invite->deliveryFailed());

        Mail::fake();
        $this->assertTrue(app(AccessInviteService::class)->resend($invite));

        $invite->refresh();
        $this->assertNotNull($invite->sent_at);
        $this->assertNull($invite->send_failed_at);
        $this->assertNull($invite->send_error);
        $this->assertTrue($invite->wasDelivered());
    }

    public function test_resend_from_the_admin_screen_reports_the_outcome(): void
    {
        $invite = $this->invite(['send_failed_at' => now(), 'send_error' => self::SMTP_ERROR]);

        Mail::fake();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->call('resendInvite', $invite->id)
            ->assertSet('feedbackStatus', 'success')
            ->assertSet('feedbackMessage', fn (string $m) => str_contains($m, 'Invite re-sent to recipient@example.com'));

        $this->assertTrue($invite->refresh()->wasDelivered());
    }

    public function test_a_resend_that_fails_again_says_so(): void
    {
        $invite = $this->invite();
        $this->failSending();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->call('resendInvite', $invite->id)
            ->assertSet('feedbackStatus', 'error')
            ->assertSet('feedbackMessage', fn (string $m) => str_contains($m, 'Still could not email'));

        $this->assertTrue($invite->refresh()->deliveryFailed());
    }

    public function test_a_claimed_invite_cannot_be_resent(): void
    {
        $invite = $this->invite(['redeemed_at' => now()]);

        $this->expectException(InvalidArgumentException::class);
        app(AccessInviteService::class)->resend($invite);
    }

    public function test_an_expired_invite_cannot_be_resent(): void
    {
        $invite = $this->invite(['expires_at' => now()->subDay()]);

        $this->expectException(InvalidArgumentException::class);
        app(AccessInviteService::class)->resend($invite);
    }

    public function test_undelivered_invites_are_flagged_on_the_page(): void
    {
        $this->invite([
            'email' => 'stranded@example.com',
            'send_failed_at' => now(),
            'send_error' => self::SMTP_ERROR,
        ]);

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->assertSee('could not be emailed')
            ->assertSee('stranded@example.com');
    }

    public function test_a_claimed_invite_is_not_flagged_as_undelivered(): void
    {
        $this->invite([
            'email' => 'claimed@example.com',
            'send_failed_at' => now(),
            'send_error' => self::SMTP_ERROR,
            'redeemed_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->assertDontSee('could not be emailed');
    }

    /**
     * Invites predating delivery tracking must not be reported as failures, but
     * should still be resendable — that is how the two stranded September
     * invites get recovered.
     */
    public function test_legacy_invites_read_as_unknown_and_stay_resendable(): void
    {
        $invite = $this->invite(['email' => 'legacy@example.com']);

        $this->assertTrue($invite->deliveryIsUnknown());
        $this->assertFalse($invite->deliveryFailed());
        $this->assertFalse($invite->wasDelivered());
        $this->assertTrue($invite->isValid());

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->assertSee('Unknown')
            ->assertSee('Resend')
            ->assertDontSee('could not be emailed');
    }
}
