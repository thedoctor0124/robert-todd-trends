<?php

namespace Tests\Feature;

use App\Livewire\AccessInvite\Claim;
use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccessInviteClaimTest extends TestCase
{
    use RefreshDatabase;

    private function publication(): Publication
    {
        $season = Season::create([
            'name' => 'Autumn/Winter', 'slug' => 'aw-2026', 'year' => 2026,
            'subscription_price' => 500, 'status' => 'published',
        ]);

        return Publication::create([
            'season_id' => $season->id, 'title' => 'Design Direction',
            'slug' => 'design-direction', 'price' => 150, 'status' => 'published',
        ]);
    }

    private function invite(string $email = 'newcomer@example.com'): AccessInvite
    {
        return AccessInvite::create([
            'token' => 'tok'.str_repeat('z', 20).uniqid(),
            'email' => $email,
            'invited_name' => 'Dylan Walters',
            'access_type' => 'publication',
            'publication_id' => $this->publication()->id,
            'granted_by' => 'admin@roberttodds.com',
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_it_opens_on_the_chooser_not_a_password_form(): void
    {
        $invite = $this->invite();

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->assertSet('authMode', 'choose')
            ->assertSee('which applies to you')
            ->assertSee('Create my account')
            ->assertSee('I already have an account')
            // The confusing part: no password field until a route is chosen.
            ->assertDontSee('Sign in &amp; open report', escape: false)
            ->assertDontSee('Confirm password');
    }

    public function test_a_recipient_without_an_account_is_steered_to_registering(): void
    {
        $invite = $this->invite('nobody@example.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->assertSee('there is no account for this email yet');
    }

    public function test_a_recipient_with_an_account_is_steered_to_signing_in(): void
    {
        User::factory()->create(['email' => 'member@example.com']);
        $invite = $this->invite('member@example.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->assertSee('signing in is the quickest route')
            ->assertDontSee('there is no account for this email yet');
    }

    public function test_choosing_register_reveals_the_registration_form(): void
    {
        $invite = $this->invite();

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'register')
            ->assertSet('authMode', 'register')
            ->assertSee('Create your account')
            ->assertSee('Confirm password')
            ->assertSee('Full name');
    }

    public function test_choosing_login_reveals_the_sign_in_form(): void
    {
        $invite = $this->invite();

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'login')
            ->assertSet('authMode', 'login')
            ->assertSee('Sign in to your account')
            ->assertDontSee('Confirm password');
    }

    public function test_back_returns_to_the_chooser_and_clears_the_password(): void
    {
        $invite = $this->invite();

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'login')
            ->set('password', 'secret-typing')
            ->call('backToChoice')
            ->assertSet('authMode', 'choose')
            ->assertSet('password', '')
            ->assertSee('which applies to you');
    }

    public function test_an_unexpected_mode_is_ignored(): void
    {
        $invite = $this->invite();

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'admin')
            ->assertSet('authMode', 'choose');
    }

    public function test_registering_through_the_chooser_claims_the_access(): void
    {
        $invite = $this->invite('newcomer@example.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'register')
            ->set('name', 'New Comer')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertNotNull($invite->refresh()->redeemed_at);
        $this->assertDatabaseHas('users', ['email' => 'newcomer@example.com']);
    }

    public function test_signing_in_through_the_chooser_claims_the_access(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('password123'),
        ]);
        $invite = $this->invite('member@example.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'login')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($invite->refresh()->redeemed_at);
    }

    public function test_a_wrong_password_keeps_the_user_on_the_sign_in_form(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('password123'),
        ]);
        $invite = $this->invite('member@example.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'login')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSet('authMode', 'login');

        $this->assertGuest();
        $this->assertNull($invite->refresh()->redeemed_at);
    }

    public function test_an_expired_invite_shows_no_chooser(): void
    {
        $invite = $this->invite();
        $invite->update(['expires_at' => now()->subDay()]);

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->assertSee('expired')
            ->assertDontSee('which applies to you');
    }
}
