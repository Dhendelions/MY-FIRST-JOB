<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class YearBasedWorkspaceArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $adminA;
    private User $adminB;
    private OperationalYear $year2026;
    private OperationalYear $year2025;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create verified Owner
        $this->owner = User::create([
            'name' => 'Owner Dadhen',
            'email' => 'owner_year_arch@test.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        // 2. Create verified Admin A
        $this->adminA = User::create([
            'name' => 'Admin Alpha',
            'email' => 'admin_a_year_arch@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);

        // 3. Create verified Admin B
        $this->adminB = User::create([
            'name' => 'Admin Beta',
            'email' => 'admin_b_year_arch@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);

        // 4. Provision 2026 and 2025 operational years with monthly workspaces & sheets
        $initializer = app(CompanyWorkspaceInitializer::class);
        $this->year2026 = $initializer->provisionYear(2026, $this->owner->id, false);
        $this->year2025 = $initializer->provisionYear(2025, $this->owner->id, false);

        // Configure passwords on both operational years
        $this->year2026->password = Hash::make('Year2026Pass');
        $this->year2026->password_version = 1;
        $this->year2026->save();

        $this->year2025->password = Hash::make('Year2025Pass');
        $this->year2025->password_version = 1;
        $this->year2025->save();
    }

    public function test_dashboard_displays_year_cards_and_not_individual_monthly_workspaces(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard'));

        $response->assertStatus(200);
        // Asserts year cards are displayed
        $response->assertSee('Workspace 2026');
        $response->assertSee('Workspace 2025');
        $response->assertSee('12 Bulan Operasional');
        
        // Ensure the old 12-month links grid is removed from dashboard
        // In the old dashboard, each month was an anchor "Januari", "Februari", etc. inside the dashboard view.
        // We ensure "Buka Workspace 2026" or "Buka Workspace" is present on the cards.
        $response->assertSee('Buka Workspace');
    }

    public function test_owner_can_access_any_year_and_monthly_workspace_without_password(): void
    {
        // Owner visits 2026 year overview directly
        $response = $this->actingAs($this->owner)->get(route('company.year', 2026));

        $response->assertStatus(200);
        $response->assertSee('Daftar Bulan Operasional');
        $response->assertSee('Januari');
        $response->assertSee('Desember');
        $response->assertDontSee('Masukkan Password Workspace 2026');

        // Owner visits January 2026 monthly workspace
        $janResponse = $this->get(route('company.workspace', [2026, 1]));
        $janResponse->assertStatus(200);

        // Owner visits February 2026 monthly workspace
        $febResponse = $this->get(route('company.workspace', [2026, 2]));
        $febResponse->assertStatus(200);

        // Owner can access restricted Margin sheet
        $marginResponse = $this->get(route('company.workspace', [2026, 1, 'Margin']));
        $marginResponse->assertStatus(200);
    }

    public function test_admin_must_authenticate_with_year_password_to_access_year_workspace(): void
    {
        // Admin A visits year 2026 when locked
        $response = $this->actingAs($this->adminA)->get(route('company.year', 2026));

        $response->assertStatus(200);
        $response->assertSee('Masukkan Password Workspace 2026');
        $response->assertDontSee('Daftar Bulan Operasional');

        // Admin A enters wrong password
        $failPost = $this->post(route('company.year.unlock', 2026), [
            'password' => 'WrongPassword',
        ]);

        $failPost->assertSessionHas('error');
        $this->assertFalse($this->year2026->isUnlockedForUser($this->adminA->id));

        // Admin A enters correct password
        $successPost = $this->post(route('company.year.unlock', 2026), [
            'password' => 'Year2026Pass',
        ]);

        $successPost->assertRedirect(route('company.year', 2026));
        $this->assertTrue($this->year2026->isUnlockedForUser($this->adminA->id));

        // Now Admin A sees the monthly workspaces
        $unlockedResponse = $this->get(route('company.year', 2026));
        $unlockedResponse->assertStatus(200);
        $unlockedResponse->assertSee('Daftar Bulan Operasional');
        $unlockedResponse->assertSee('Januari');

        $this->get(route('dashboard'))->assertOk();
        $this->assertFalse($this->year2026->isUnlockedForUser($this->adminA->id));
        $this->get(route('company.year', 2026))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace 2026');
    }

    public function test_each_month_requires_its_password_and_sheet_navigation_stays_authorized(): void
    {
        $this->actingAs($this->adminA);
        $this->year2026->unlockForUser($this->adminA->id);

        $january = $this->year2026->monthlyWorkspaces()->where('month', 1)->firstOrFail()->group;
        $janResponse = $this->get(route('company.workspace', [2026, 1]));
        $janResponse->assertStatus(200);
        $janResponse->assertViewHas('requiresPasswordUnlock', true);

        $this->post(route('groups.unlock', $january), [
            'password' => 'Year2026Pass',
        ])->assertRedirect();
        $this->get(route('company.workspace', [2026, 1, 'Cost']))
            ->assertOk()
            ->assertViewHas('requiresPasswordUnlock', false);

        // A different month is a different workspace and requires verification.
        $febResponse = $this->get(route('company.workspace', [2026, 2]));
        $febResponse->assertStatus(200);
        $febResponse->assertViewHas('requiresPasswordUnlock', true);

        $february = $this->year2026->monthlyWorkspaces()->where('month', 2)->firstOrFail()->group;
        $this->post(route('groups.unlock', $february), [
            'password' => 'Year2026Pass',
        ])->assertRedirect();
        $decResponse = $this->get(route('company.workspace', [2026, 12]));
        $decResponse->assertStatus(200);
        $decResponse->assertViewHas('requiresPasswordUnlock', true);
    }

    public function test_switching_sheets_in_one_month_does_not_reprompt(): void
    {
        $this->actingAs($this->adminA);
        $group = $this->year2026->monthlyWorkspaces()->where('month', 1)->firstOrFail()->group;
        $this->post(route('groups.unlock', $group), [
            'password' => 'Year2026Pass',
        ])->assertRedirect();

        // Admin can access Analisa
        $analisaResponse = $this->get(route('company.workspace', [2026, 1, 'Analisa']));
        $analisaResponse->assertStatus(200);

        // Admin can switch to Cost
        $costResponse = $this->get(route('company.workspace', [2026, 1, 'Cost']));
        $costResponse->assertStatus(200);

        // Admin viewing Margin sees restricted access banner and no spreadsheet grid
        $marginResponse = $this->get(route('company.workspace', [2026, 1, 'Margin']));
        $marginResponse->assertStatus(200);
        $marginResponse->assertSee('Akses Lembar Kerja Dibatasi');
        $marginResponse->assertDontSee('spreadsheet-grid-table');

        // Backend enforcement: direct API export on Margin is strictly forbidden (403)
        $firstMw = $this->year2026->monthlyWorkspaces()->first();
        $marginSheet = $firstMw->group->sheets()->where('name', 'Margin')->first();
        $pendukungSheet = $firstMw->group->sheets()->where('name', 'Pendukung')->first();
        $rankSheet = $firstMw->group->sheets()->where('name', 'Rank')->first();

        $this->get(route('spreadsheet.export', $marginSheet->id))->assertStatus(403);
        $this->get(route('spreadsheet.export', $pendukungSheet->id))->assertStatus(403);
        $this->get(route('spreadsheet.export', $rankSheet->id))->assertStatus(403);
    }

    public function test_switching_between_different_years_requires_authenticating_the_new_year(): void
    {
        $this->actingAs($this->adminA);

        // Unlock year 2026 only
        $this->year2026->unlockForUser($this->adminA->id);
        $this->assertTrue($this->year2026->isUnlockedForUser($this->adminA->id));
        $this->assertFalse($this->year2025->isUnlockedForUser($this->adminA->id));

        // 2026 is unlocked
        $year2026Response = $this->get(route('company.year', 2026));
        $year2026Response->assertSee('Daftar Bulan Operasional');

        // 2025 is LOCKED
        $year2025Response = $this->get(route('company.year', 2025));
        $year2025Response->assertSee('Masukkan Password Workspace 2025');
        $year2025Response->assertDontSee('Daftar Bulan Operasional');

        // A direct URL to a month displays its own workspace-password screen.
        $direct2025 = $this->get(route('company.workspace', [2025, 1]));
        $direct2025->assertOk();
        $direct2025->assertViewHas('requiresPasswordUnlock', true);
    }

    public function test_admin_a_unlocking_year_does_not_unlock_for_admin_b(): void
    {
        // Admin A unlocks year 2026
        $this->actingAs($this->adminA);
        $this->year2026->unlockForUser($this->adminA->id);
        $this->assertTrue($this->year2026->isUnlockedForUser($this->adminA->id));

        // Admin B logs in
        $this->actingAs($this->adminB);
        $this->assertFalse($this->year2026->isUnlockedForUser($this->adminB->id));

        $responseB = $this->get(route('company.year', 2026));
        $responseB->assertSee('Masukkan Password Workspace 2026');
        $responseB->assertDontSee('Daftar Bulan Operasional');
    }

    public function test_owner_changing_year_password_increments_password_version_and_invalidates_admin_sessions(): void
    {
        // Admin A unlocks year 2026
        $this->actingAs($this->adminA);
        $this->year2026->unlockForUser($this->adminA->id);
        $this->assertTrue($this->year2026->isUnlockedForUser($this->adminA->id));

        // Owner changes password for year 2026
        $this->actingAs($this->owner);
        $changePasswordPost = $this->post(route('company.year.password', 2026), [
            'password' => 'NewYearPass2026',
            'password_confirmation' => 'NewYearPass2026',
        ]);
        $changePasswordPost->assertSessionHas('success');

        $this->year2026->refresh();
        $this->assertEquals(2, $this->year2026->password_version);

        // Admin A now accesses year 2026 -> session is invalidated!
        $this->actingAs($this->adminA);
        $this->assertFalse($this->year2026->isUnlockedForUser($this->adminA->id));

        $response = $this->get(route('company.year', 2026));
        $response->assertSee('Masukkan Password Workspace 2026');
        $response->assertDontSee('Daftar Bulan Operasional');

        // Admin A enters the new password -> unlocked again!
        $unlockPost = $this->post(route('company.year.unlock', 2026), [
            'password' => 'NewYearPass2026',
        ]);
        $unlockPost->assertRedirect(route('company.year', 2026));
        $this->assertTrue($this->year2026->isUnlockedForUser($this->adminA->id));
    }

    public function test_owner_can_remove_year_password(): void
    {
        $this->actingAs($this->owner);

        // Owner submits empty password to remove protection
        $removePass = $this->post(route('company.year.password', 2026), [
            'password' => '',
            'password_confirmation' => '',
        ]);
        $removePass->assertSessionHas('success');

        $this->year2026->refresh();
        $this->assertNull($this->year2026->password);
        $this->assertFalse($this->year2026->hasPassword());

        // Admin A can now access without entering any password
        $this->actingAs($this->adminA);
        $this->assertTrue($this->year2026->isUnlockedForUser($this->adminA->id));

        $response = $this->get(route('company.year', 2026));
        $response->assertStatus(200);
        $response->assertSee('Daftar Bulan Operasional');
    }

    public function test_direct_url_to_spreadsheet_export_requires_monthly_workspace_authentication(): void
    {
        $monthlyWorkspace = $this->year2026->monthlyWorkspaces()->where('month', 1)->first();
        $analisaSheet = $monthlyWorkspace->group->sheets()->where('name', 'Analisa')->first();

        // 1. Locked Admin tries to export
        $this->actingAs($this->adminA);
        $response = $this->get(route('spreadsheet.export', $analisaSheet->id));
        $response->assertStatus(403);

        // 2. Unlocking the year overview alone does not unlock a monthly workspace.
        $this->year2026->unlockForUser($this->adminA->id);
        $this->get(route('spreadsheet.export', $analisaSheet->id))->assertStatus(403);

        $this->post(route('groups.unlock', $monthlyWorkspace->group), [
            'password' => 'Year2026Pass',
        ])->assertRedirect();
        $exportSuccess = $this->get(route('spreadsheet.export', $analisaSheet->id));
        $exportSuccess->assertStatus(200);
    }

    public function test_dynamic_role_demotion_from_owner_to_admin_enforces_year_password_immediately(): void
    {
        // User starts as Owner
        $dynamicUser = User::create([
            'name' => 'Dynamic Role User',
            'email' => 'dynamic_user@test.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        $this->actingAs($dynamicUser);
        $this->assertTrue($this->year2026->isUnlockedForUser($dynamicUser->id));

        // Demote to Admin in DB
        $dynamicUser->update(['role' => 'admin', 'role_title' => 'Admin']);
        $dynamicUser->refresh();

        $this->assertFalse($this->year2026->isUnlockedForUser($dynamicUser->id));
        $response = $this->get(route('company.year', 2026));
        $response->assertSee('Masukkan Password Workspace 2026');
    }
}
