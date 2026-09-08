<?php

namespace Tests\Feature;

use App\Livewire\Admin\BulkSendAccess;
use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class BulkSendAccessTest extends TestCase
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

    /** Run a batch to completion, mimicking the view's polling. */
    private function drain($component)
    {
        $guard = 0;

        while ($component->get('sending') && $guard++ < 250) {
            $component->call('processNext');
        }

        return $component;
    }

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('recipients.csv', $body);
    }

    public function test_the_page_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.send-access.bulk'))
            ->assertOk()
            ->assertSee('Bulk send access links')
            ->assertSee('Company')
            ->assertSee('Access for everyone in this list');
    }

    public function test_it_is_closed_to_non_admins(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'punter@example.com']))
            ->get(route('admin.send-access.bulk'))
            ->assertForbidden();
    }

    public function test_it_sends_an_invite_for_every_filled_row(): void
    {
        Mail::fake();
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [
                ['company' => 'Vogue', 'name' => 'Ada Lovelace', 'email' => 'ada@vogue.com'],
                ['company' => 'Selfridges', 'name' => 'Cara Hughes', 'email' => 'cara@selfridges.com'],
                ['company' => '', 'name' => '', 'email' => ''],
            ])
            ->set('accessType', 'publication')
            ->set('grantItemId', $publication->id)
            ->call('startSend')
            ->assertHasNoErrors();

        $this->drain($component)->assertSet('sending', false);

        $this->assertSame(2, AccessInvite::count());
        $this->assertSame('Vogue', AccessInvite::where('email', 'ada@vogue.com')->value('invited_company'));
        $this->assertSame('Cara Hughes', AccessInvite::where('email', 'cara@selfridges.com')->value('invited_name'));
    }

    public function test_blank_rows_are_ignored_rather_than_reported(): void
    {
        Mail::fake();
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [
                ['company' => '', 'name' => '', 'email' => ''],
                ['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com'],
                ['company' => '', 'name' => '', 'email' => ''],
            ])
            ->set('grantItemId', $publication->id)
            ->call('startSend')
            ->assertHasNoErrors();

        $this->drain($component);

        $this->assertSame(1, AccessInvite::count());
    }

    public function test_a_row_missing_an_email_is_rejected(): void
    {
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => 'Ada', 'email' => '']])
            ->set('grantItemId', $publication->id)
            ->call('startSend')
            ->assertHasErrors(['rows.0.email' => 'required'])
            ->assertSet('sending', false);

        $this->assertSame(0, AccessInvite::count());
    }

    public function test_a_row_missing_a_name_is_rejected(): void
    {
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => '', 'email' => 'ada@vogue.com']])
            ->set('grantItemId', $publication->id)
            ->call('startSend')
            ->assertHasErrors(['rows.0.name' => 'required']);
    }

    public function test_access_must_be_chosen_before_sending(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com']])
            ->set('grantItemId', 0)
            ->call('startSend')
            ->assertHasErrors(['grantItemId' => 'min'])
            ->assertSet('sending', false);
    }

    public function test_an_empty_sheet_is_refused(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->call('startSend')
            ->assertSet('feedbackStatus', 'error')
            ->assertSet('feedbackMessage', 'Add at least one recipient before sending.');
    }

    public function test_a_duplicate_email_blocks_the_batch(): void
    {
        $publication = $this->publication();

        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [
                ['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com'],
                ['company' => 'Vogue', 'name' => 'Ada Again', 'email' => 'ADA@vogue.com'],
            ])
            ->set('grantItemId', $publication->id)
            ->call('startSend')
            ->assertHasErrors('rows.1.email')
            ->assertSet('sending', false);

        $this->assertSame(0, AccessInvite::count());
    }

    public function test_an_existing_customer_is_recognised_and_their_company_filled_in(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ada@vogue.com', 'name' => 'Ada Lovelace', 'company' => null]);
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => 'Ada Lovelace', 'email' => 'ada@vogue.com']])
            ->set('grantItemId', $publication->id)
            ->call('startSend');

        $this->drain($component);

        $this->assertSame('Vogue', $user->refresh()->company);
        $this->assertSame('Existing customer', $component->get('results')[0]['message']);
        $this->assertSame($user->id, AccessInvite::where('email', 'ada@vogue.com')->value('user_id'));
    }

    public function test_a_failed_send_is_reported_per_row_without_stopping_the_batch(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('535 5.7.8 BadCredentials'));
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [
                ['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com'],
                ['company' => 'Selfridges', 'name' => 'Cara', 'email' => 'cara@selfridges.com'],
            ])
            ->set('grantItemId', $publication->id)
            ->call('startSend');

        $this->drain($component)->assertSet('sending', false);

        $results = $component->get('results');
        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame('failed', $result['status']);
            $this->assertStringContainsString('BadCredentials', $result['message']);
        }

        // The invites still exist, so they can be resent once mail is fixed.
        $this->assertSame(2, AccessInvite::count());
        $component->assertSet('feedbackStatus', 'error');
    }

    public function test_the_sheet_is_kept_when_something_failed_so_it_can_be_retried(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('nope'));
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com']])
            ->set('grantItemId', $publication->id)
            ->call('startSend');

        $this->drain($component);

        $this->assertSame('ada@vogue.com', $component->get('rows')[0]['email']);
    }

    public function test_the_sheet_is_cleared_after_a_clean_run(): void
    {
        Mail::fake();
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com']])
            ->set('grantItemId', $publication->id)
            ->call('startSend');

        $this->drain($component)
            ->assertSet('feedbackStatus', 'success')
            ->assertSet('feedbackMessage', 'Sent 1 access link.');

        $this->assertSame('', $component->get('rows')[0]['email']);
    }

    public function test_rows_can_be_added_and_removed(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->assertCount('rows', 5)
            ->call('addRow')
            ->assertCount('rows', 6)
            ->call('removeRow', 0)
            ->assertCount('rows', 5);
    }

    public function test_removing_every_row_leaves_one_to_type_into(): void
    {
        $component = Livewire::actingAs($this->admin())->test(BulkSendAccess::class);

        for ($i = 0; $i < 6; $i++) {
            $component->call('removeRow', 0);
        }

        $component->assertCount('rows', 1);
    }

    public function test_a_csv_with_a_header_populates_the_rows(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv(
                "Company,Name,Email\n".
                "Vogue,Ada Lovelace,ada@vogue.com\n".
                "Selfridges,Cara Hughes,cara@selfridges.com\n"
            ))
            ->assertHasNoErrors()
            ->assertCount('rows', 2)
            ->assertSet('rows.0.company', 'Vogue')
            ->assertSet('rows.0.email', 'ada@vogue.com')
            ->assertSet('rows.1.name', 'Cara Hughes')
            ->assertSet('csvMessage', 'Loaded 2 rows from the file.');
    }

    public function test_a_csv_header_may_be_in_any_column_order(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv("Email,Company,Name\nada@vogue.com,Vogue,Ada Lovelace\n"))
            ->assertSet('rows.0.email', 'ada@vogue.com')
            ->assertSet('rows.0.company', 'Vogue')
            ->assertSet('rows.0.name', 'Ada Lovelace');
    }

    public function test_a_csv_without_a_header_is_read_in_column_order(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv("Vogue,Ada Lovelace,ada@vogue.com\n"))
            ->assertCount('rows', 1)
            ->assertSet('rows.0.company', 'Vogue')
            ->assertSet('rows.0.email', 'ada@vogue.com');
    }

    public function test_a_byte_order_mark_does_not_corrupt_the_first_column(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv("\xEF\xBB\xBFCompany,Name,Email\nVogue,Ada,ada@vogue.com\n"))
            ->assertSet('rows.0.company', 'Vogue')
            ->assertCount('rows', 1);
    }

    public function test_blank_csv_lines_are_skipped(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv("Company,Name,Email\nVogue,Ada,ada@vogue.com\n\n\nSelfridges,Cara,cara@s.com\n"))
            ->assertCount('rows', 2);
    }

    public function test_a_csv_with_no_usable_rows_says_so(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv("\n\n"))
            ->assertSet('csvMessage', 'No rows found. Expected columns: Company, Name, Email.');
    }

    public function test_a_csv_import_can_be_sent_straight_away(): void
    {
        Mail::fake();
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('csv', $this->csv("Company,Name,Email\nVogue,Ada,ada@vogue.com\nSelfridges,Cara,cara@s.com\n"))
            ->set('grantItemId', $publication->id)
            ->call('startSend')
            ->assertHasNoErrors();

        $this->drain($component);

        $this->assertSame(2, AccessInvite::count());
    }

    public function test_a_subscription_batch_grants_seasons(): void
    {
        Mail::fake();
        $publication = $this->publication();
        $season = $publication->season;

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [['company' => 'Vogue', 'name' => 'Ada', 'email' => 'ada@vogue.com']])
            ->set('accessType', 'subscription')
            ->set('grantItemId', $season->id)
            ->call('startSend')
            ->assertHasNoErrors();

        $this->drain($component);

        $invite = AccessInvite::where('email', 'ada@vogue.com')->firstOrFail();
        $this->assertSame('subscription', $invite->access_type);
        $this->assertSame($season->id, $invite->season_id);
    }

    /**
     * The batch must advance one row at a time; doing the lot in one request
     * would exceed the 30s PHP-FPM limit on a real send.
     */
    public function test_each_request_sends_one_invite(): void
    {
        Mail::fake();
        $publication = $this->publication();

        $component = Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->set('rows', [
                ['company' => 'A', 'name' => 'One', 'email' => 'one@example.com'],
                ['company' => 'B', 'name' => 'Two', 'email' => 'two@example.com'],
                ['company' => 'C', 'name' => 'Three', 'email' => 'three@example.com'],
            ])
            ->set('grantItemId', $publication->id)
            ->call('startSend');

        $component->assertSet('totalToSend', 3)->assertSet('sending', true);

        $component->call('processNext');
        $this->assertCount(1, $component->get('results'));
        $this->assertSame(1, AccessInvite::count());
        $component->assertSet('sending', true);

        $component->call('processNext');
        $this->assertCount(2, $component->get('results'));

        $component->call('processNext');
        $this->assertCount(3, $component->get('results'));
        $component->assertSet('sending', false);
    }

    public function test_processing_does_nothing_when_no_batch_is_running(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BulkSendAccess::class)
            ->call('processNext')
            ->assertSet('results', [])
            ->assertSet('sending', false);

        $this->assertSame(0, AccessInvite::count());
    }
}
