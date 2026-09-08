<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\Index;
use App\Livewire\Admin\Users\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserCompanyTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->create([
            'name' => 'Admin Person',
            'email' => 'admin@roberttodds.com',
            'is_admin' => true,
        ]);
    }

    private function seedUsers(): void
    {
        User::factory()->create(['name' => 'Ada Vogue', 'email' => 'ada@vogue.com', 'company' => 'Vogue']);
        User::factory()->create(['name' => 'Ben Vogue', 'email' => 'ben@vogue.com', 'company' => 'Vogue']);
        User::factory()->create(['name' => 'Cara Selfridge', 'email' => 'cara@selfridges.com', 'company' => 'Selfridges']);
        User::factory()->create(['name' => 'Dan Nobody', 'email' => 'dan@example.com', 'company' => null]);
        User::factory()->create(['name' => 'Eve Blank', 'email' => 'eve@example.com', 'company' => '']);
    }

    public function test_the_company_column_is_shown(): void
    {
        $this->seedUsers();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Company')
            ->assertSee('Vogue')
            ->assertSee('Selfridges');
    }

    public function test_filtering_by_company_narrows_the_list(): void
    {
        $this->seedUsers();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('company', 'Vogue')
            ->assertSee('Ada Vogue')
            ->assertSee('Ben Vogue')
            ->assertDontSee('Cara Selfridge')
            ->assertDontSee('Dan Nobody');
    }

    public function test_the_company_dropdown_lists_each_company_once(): void
    {
        $this->seedUsers();

        // Two Vogue users must not produce two Vogue options.
        $companies = Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->viewData('companies');

        $this->assertSame(['Selfridges', 'Vogue'], $companies->all());
    }

    public function test_users_without_a_company_can_be_isolated(): void
    {
        $this->seedUsers();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('company', Index::NO_COMPANY)
            ->assertSee('Dan Nobody')
            // A blank string counts as "no company", same as NULL.
            ->assertSee('Eve Blank')
            ->assertDontSee('Ada Vogue');
    }

    public function test_the_missing_company_count_covers_null_and_blank(): void
    {
        $this->seedUsers();

        // Dan (null), Eve (blank) and the admin account itself.
        $this->assertSame(3, Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->viewData('missingCompanyCount'));
    }

    public function test_search_matches_company_as_well_as_name_and_email(): void
    {
        $this->seedUsers();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('search', 'selfridge')
            ->assertSee('Cara Selfridge')
            ->assertDontSee('Ada Vogue');
    }

    public function test_search_and_company_filter_combine(): void
    {
        $this->seedUsers();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('company', 'Vogue')
            ->set('search', 'Ada')
            ->assertSee('Ada Vogue')
            ->assertDontSee('Ben Vogue');
    }

    public function test_clearing_filters_restores_the_full_list(): void
    {
        $this->seedUsers();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('company', 'Vogue')
            ->set('search', 'Ada')
            ->call('clearFilters')
            ->assertSet('company', '')
            ->assertSet('search', '')
            ->assertSee('Cara Selfridge')
            ->assertSee('Dan Nobody');
    }

    public function test_changing_the_company_filter_resets_pagination(): void
    {
        User::factory()->count(30)->create(['company' => 'Vogue']);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('paginators.page', 2)
            ->set('company', 'Vogue')
            ->assertSet('paginators.page', 1);
    }

    public function test_an_admin_can_set_a_company_on_a_user(): void
    {
        $user = User::factory()->create(['company' => null]);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['user' => $user])
            ->set('company', '  Harvey Nichols  ')
            ->call('saveCompany')
            ->assertHasNoErrors()
            ->assertSet('companySaved', true);

        // Stored trimmed.
        $this->assertSame('Harvey Nichols', $user->refresh()->company);
    }

    public function test_clearing_a_company_stores_null_rather_than_a_blank(): void
    {
        $user = User::factory()->create(['company' => 'Vogue']);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['user' => $user])
            ->set('company', '   ')
            ->call('saveCompany')
            ->assertHasNoErrors();

        $this->assertNull($user->refresh()->company);
    }

    public function test_an_overlong_company_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['user' => $user])
            ->set('company', str_repeat('a', 256))
            ->call('saveCompany')
            ->assertHasErrors(['company' => 'max']);

        $this->assertNull($user->refresh()->company);
    }

    public function test_the_user_list_stays_admin_only(): void
    {
        $user = User::factory()->create(['email' => 'punter@example.com']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
