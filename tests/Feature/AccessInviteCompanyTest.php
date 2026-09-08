<?php

namespace Tests\Feature;

use App\Livewire\AccessInvite\Claim;
use App\Livewire\Admin\SendAccess;
use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AccessInviteCompanyTest extends TestCase
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

    private function publication(): Publication
    {
        $season = Season::create([
            'name' => 'Autumn/Winter', 'slug' => 'aw-'.uniqid(), 'year' => 2026,
            'subscription_price' => 500, 'status' => 'published',
        ]);

        return Publication::create([
            'season_id' => $season->id, 'title' => 'Design Direction',
            'slug' => 'dd-'.uniqid(), 'price' => 150, 'status' => 'published',
        ]);
    }

    private function sendToNewCustomer(string $company, string $email = 'jane@company.com'): AccessInvite
    {
        Mail::fake();
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'new')
            ->set('invitedName', 'Jane Smith')
            ->set('email', $email)
            ->set('company', $company)
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        return AccessInvite::where('email', $email)->firstOrFail();
    }

    public function test_a_company_typed_for_a_new_customer_is_kept_on_the_invite(): void
    {
        $invite = $this->sendToNewCustomer('Selfridges');

        $this->assertSame('Selfridges', $invite->invited_company);
    }

    public function test_the_company_is_trimmed(): void
    {
        $invite = $this->sendToNewCustomer('   Harvey Nichols   ');

        $this->assertSame('Harvey Nichols', $invite->invited_company);
    }

    public function test_leaving_the_company_blank_is_allowed(): void
    {
        $invite = $this->sendToNewCustomer('');

        $this->assertNull($invite->invited_company);
    }

    public function test_the_company_lands_on_the_account_when_the_invite_is_claimed(): void
    {
        $invite = $this->sendToNewCustomer('Selfridges', 'jane@company.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'register')
            ->set('name', 'Jane Smith')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertSame('Selfridges', User::where('email', 'jane@company.com')->value('company'));
    }

    public function test_claiming_an_invite_with_no_company_leaves_the_account_blank(): void
    {
        $invite = $this->sendToNewCustomer('', 'nocompany@example.com');

        Livewire::test(Claim::class, ['token' => $invite->token])
            ->call('chooseMode', 'register')
            ->set('name', 'No Company')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertNull(User::where('email', 'nocompany@example.com')->value('company'));
    }

    public function test_picking_an_existing_customer_prefills_their_company(): void
    {
        $user = User::factory()->create(['email' => 'ada@vogue.com', 'name' => 'Ada', 'company' => 'Vogue']);

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'existing')
            ->set('userId', $user->id)
            ->assertSet('company', 'Vogue');
    }

    public function test_the_user_deep_link_prefills_the_company(): void
    {
        $user = User::factory()->create(['email' => 'ada@vogue.com', 'name' => 'Ada', 'company' => 'Vogue']);

        Livewire::actingAs($this->admin())
            ->withQueryParams(['user' => $user->id])
            ->test(SendAccess::class)
            ->assertSet('company', 'Vogue');
    }

    public function test_editing_the_company_for_an_existing_customer_updates_their_account(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ada@vogue.com', 'company' => 'Vogue']);
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'existing')
            ->set('company', SendAccess::NEW_COMPANY)
            ->set('newCompany', 'Conde Nast')
            ->set('userId', $user->id)
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertSame('Conde Nast', $user->refresh()->company);
        $this->assertSame('Conde Nast', AccessInvite::where('email', 'ada@vogue.com')->value('invited_company'));
    }

    /**
     * '' means "all companies" now that the field also narrows the customer
     * list, so it must not be read as an instruction to wipe the account.
     * Clearing a company is done from the user's Manage page.
     */
    public function test_leaving_the_company_on_all_does_not_wipe_an_existing_one(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ada@vogue.com', 'company' => 'Vogue']);
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'existing')
            ->set('company', '')
            ->set('userId', $user->id)
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertSame('Vogue', $user->refresh()->company);
    }

    public function test_an_existing_customers_company_is_recorded_on_the_invite_untouched(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ada@vogue.com', 'company' => 'Vogue']);
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'existing')
            ->set('userId', $user->id)
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertSame('Vogue', $user->refresh()->company);
        $this->assertSame('Vogue', AccessInvite::where('email', 'ada@vogue.com')->value('invited_company'));
    }

    public function test_the_customer_list_narrows_to_the_chosen_company(): void
    {
        User::factory()->create(['name' => 'Ada Vogue', 'email' => 'ada@vogue.com', 'company' => 'Vogue']);
        User::factory()->create(['name' => 'Cara Selfridge', 'email' => 'cara@selfridges.com', 'company' => 'Selfridges']);
        User::factory()->create(['name' => 'Dan Nobody', 'email' => 'dan@example.com', 'company' => null]);

        $names = Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('company', 'Vogue')
            ->viewData('users')
            ->pluck('name');

        $this->assertContains('Ada Vogue', $names);
        $this->assertNotContains('Cara Selfridge', $names);
        $this->assertNotContains('Dan Nobody', $names);
    }

    public function test_no_company_chosen_lists_every_customer(): void
    {
        User::factory()->create(['name' => 'Ada Vogue', 'company' => 'Vogue']);
        User::factory()->create(['name' => 'Dan Nobody', 'company' => null]);

        $names = Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->viewData('users')
            ->pluck('name');

        $this->assertContains('Ada Vogue', $names);
        $this->assertContains('Dan Nobody', $names);
    }

    public function test_the_no_company_option_surfaces_unassigned_customers(): void
    {
        User::factory()->create(['name' => 'Ada Vogue', 'company' => 'Vogue']);
        User::factory()->create(['name' => 'Dan Nobody', 'company' => null]);
        User::factory()->create(['name' => 'Eve Blank', 'company' => '']);

        $names = Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('company', SendAccess::NO_COMPANY)
            ->viewData('users')
            ->pluck('name');

        $this->assertContains('Dan Nobody', $names);
        $this->assertContains('Eve Blank', $names);
        $this->assertNotContains('Ada Vogue', $names);
    }

    /**
     * The whole point of the reordering: find someone with no company, then
     * give them one. Switching the company must not drop the chosen customer.
     */
    public function test_an_unassigned_customer_can_be_given_a_company(): void
    {
        Mail::fake();
        $dan = User::factory()->create(['name' => 'Dan Nobody', 'email' => 'dan@example.com', 'company' => null]);
        User::factory()->create(['name' => 'Ada Vogue', 'company' => 'Vogue']);
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('company', SendAccess::NO_COMPANY)
            ->set('userId', $dan->id)
            ->set('company', 'Vogue')
            ->assertSet('userId', $dan->id);

        // He is outside the Vogue filter but must remain selectable.
        $this->assertContains('Dan Nobody', $component->viewData('users')->pluck('name'));

        $component->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertSame('Vogue', $dan->refresh()->company);
    }

    public function test_a_brand_new_company_can_be_added_for_an_existing_customer(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'dan@example.com', 'company' => null]);
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('company', SendAccess::NEW_COMPANY)
            ->set('newCompany', '  Harvey Nichols  ')
            ->set('userId', $user->id)
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertSame('Harvey Nichols', $user->refresh()->company);
    }

    public function test_choosing_add_new_company_requires_a_name(): void
    {
        $user = User::factory()->create();
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('company', SendAccess::NEW_COMPANY)
            ->set('newCompany', '')
            ->set('userId', $user->id)
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasErrors(['newCompany' => 'required']);

        $this->assertDatabaseCount('access_invites', 0);
    }

    public function test_switching_away_from_add_new_company_discards_the_typed_name(): void
    {
        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('company', SendAccess::NEW_COMPANY)
            ->set('newCompany', 'Typed Then Abandoned')
            ->set('company', 'Vogue')
            ->assertSet('newCompany', '');
    }

    public function test_the_company_dropdown_lists_each_company_once(): void
    {
        User::factory()->create(['company' => 'Vogue']);
        User::factory()->create(['company' => 'Vogue']);
        User::factory()->create(['company' => 'Selfridges']);

        $companies = Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->viewData('companies');

        $this->assertSame(['Selfridges', 'Vogue'], $companies->all());
    }

    public function test_an_overlong_company_is_rejected(): void
    {
        Mail::fake();
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'new')
            ->set('invitedName', 'Jane Smith')
            ->set('email', 'jane@company.com')
            ->set('company', str_repeat('a', 256))
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('sendInvite')
            ->assertHasErrors(['company' => 'max']);

        $this->assertDatabaseCount('access_invites', 0);
    }

    public function test_the_form_shows_a_company_field_with_mode_specific_help(): void
    {
        // The form opens on 'existing', where the field is prefilled and edits
        // write back to the account.
        $this->actingAs($this->admin())
            ->get(route('admin.send-access'))
            ->assertOk()
            ->assertSee('Company')
            ->assertSee('Choose a company to narrow the customer list below.');

        Livewire::actingAs($this->admin())
            ->test(SendAccess::class)
            ->set('recipientMode', 'new')
            ->assertSee('Saved to their account when they claim the link');
    }
}
