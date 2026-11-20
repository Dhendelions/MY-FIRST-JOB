<?php

namespace Tests\Feature;

use App\Models\OperationalYear;
use App\Models\User;
use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminYearSwitchingAndReauthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private OperationalYear $year2024;
    private OperationalYear $year2025;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner Mandiri',
            'email' => 'owner_switch_test@test.com',
            'password' => bcrypt('ownerpassword123'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin_switch_test@test.com',
            'password' => bcrypt('adminaccountpass'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);

        $initializer = app(CompanyWorkspaceInitializer::class);
        $this->year2024 = $initializer->provisionYear(2024, $this->owner->id, false);
        $this->year2025 = $initializer->provisionYear(2025, $this->owner->id, false);

        // Configure passwords for both years
        $this->year2024->password = Hash::make('workspacesecret2024');
        $this->year2024->password_version = 1;
        $this->year2024->save();

        $this->year2025->password = Hash::make('workspacesecret2025');
        $this->year2025->password_version = 1;
        $this->year2025->save();
    }

    /**
     * Skenario 1 — Masuk workspace: Admin enters workspace with valid workspace password, access granted without 403.
     */
    public function test_admin_unlocks_year_2024_and_accesses_all_months_without_403()
    {
        // 1. Visit year page initially: not unlocked
        $response = $this->actingAs($this->admin)->get(route('company.year', 2024));
        $response->assertStatus(200);
        $response->assertSee('Masukkan Password Workspace');

        // 2. Unlock year 2024 with valid workspace password
        $unlockResp = $this->actingAs($this->admin)->post(route('company.year.unlock', 2024), [
            'password' => 'workspacesecret2024',
        ]);
        $unlockResp->assertRedirect(route('company.year', 2024));

        // 3. Confirm 2024 is unlocked
        $this->assertTrue($this->year2024->fresh()->isUnlockedForUser($this->admin->id));

        // 4. Access Month 1 (January)
        $m1Resp = $this->actingAs($this->admin)->get(route('company.workspace', [
            'year' => 2024,
            'month' => 1,
        ]));
        $m1Resp->assertStatus(200);

        // 5. Access Month 6 (June) without repeated password prompts or 403
        $m6Resp = $this->actingAs($this->admin)->get(route('company.workspace', [
            'year' => 2024,
            'month' => 6,
        ]));
        $m6Resp->assertStatus(200);

        // 6. Access Month 12 (December) without 403
        $m12Resp = $this->actingAs($this->admin)->get(route('company.workspace', [
            'year' => 2024,
            'month' => 12,
        ]));
        $m12Resp->assertStatus(200);
    }

    /**
     * Skenario 2 & 3 — Berpindah tahun & berpindah bulan:
     * Admin switches from 2024 to 2025 using 2025 workspace password,
     * accesses all months of 2025 without password or 403.
     */
    public function test_year_switching_requires_target_workspace_password_and_accesses_all_months()
    {
        // 1. Admin unlocks Year 2024
        $this->actingAs($this->admin)->post(route('company.year.unlock', 2024), [
            'password' => 'workspacesecret2024',
        ]);
        $this->assertTrue($this->year2024->fresh()->isUnlockedForUser($this->admin->id));

        // 2. Admin switches to Year 2025 (not yet unlocked)
        $resp2025 = $this->actingAs($this->admin)->get(route('company.year', 2025));
        $resp2025->assertStatus(200);
        $resp2025->assertSee('Masukkan Password Workspace');

        // Direct month navigation in 2025 safely redirects to year password gate (no raw 403)
        $unauthMonth = $this->actingAs($this->admin)->get(route('company.workspace', ['year' => 2025, 'month' => 1]));
        $unauthMonth->assertRedirect(route('company.year', 2025));

        // 3. Admin unlocks Year 2025 with 2025 workspace password
        $unlock2025 = $this->actingAs($this->admin)->post(route('company.year.unlock', 2025), [
            'password' => 'workspacesecret2025',
        ]);
        $unlock2025->assertRedirect(route('company.year', 2025));

        // Year 2025 is now unlocked
        $this->assertTrue($this->year2025->fresh()->isUnlockedForUser($this->admin->id));

        // Skenario 3 — Month switching within 2025: seamlessly access Month 1 to Month 12 without 403
        $m1Resp = $this->actingAs($this->admin)->get(route('company.workspace', ['year' => 2025, 'month' => 1]));
        $m1Resp->assertStatus(200);

        $m6Resp = $this->actingAs($this->admin)->get(route('company.workspace', ['year' => 2025, 'month' => 6]));
        $m6Resp->assertStatus(200);

        $m12Resp = $this->actingAs($this->admin)->get(route('company.workspace', ['year' => 2025, 'month' => 12]));
        $m12Resp->assertStatus(200);
    }

    /**
     * Skenario 4 — Menu Dashboard:
     * Leaving workspace towards Dashboard requires Admin account password (not workspace password).
     */
    public function test_leaving_workspace_towards_dashboard_requires_admin_account_password()
    {
        // Unlock 2024 first
        $this->actingAs($this->admin)->post(route('company.year.unlock', 2024), ['password' => 'workspacesecret2024']);
        $group2024 = $this->year2024->monthlyWorkspaces()->first()->group;

        // 1. Entering wrong account password rejects exit
        $wrongResp = $this->actingAs($this->admin)->postJson(route('groups.leave', $group2024->id), [
            'destination' => route('dashboard'),
            'password' => 'wrongaccountpassword',
        ]);
        $wrongResp->assertStatus(422);
        $wrongResp->assertJsonFragment(['status' => 'error']);

        // Workspace session is still intact
        $this->assertTrue($this->year2024->fresh()->isUnlockedForUser($this->admin->id));

        // 2. Entering correct Admin account password ('adminaccountpass') successfully clears workspace and redirects
        $correctResp = $this->actingAs($this->admin)->postJson(route('groups.leave', $group2024->id), [
            'destination' => route('dashboard'),
            'password' => 'adminaccountpass',
        ]);
        $correctResp->assertStatus(200);
        $correctResp->assertJsonFragment(['redirect' => route('dashboard')]);

        // Workspace authentication is terminated
        $this->assertFalse($this->year2024->fresh()->isUnlockedForUser($this->admin->id));

        // Admin remains authenticated in main app
        $this->assertAuthenticatedAs($this->admin);
    }

    /**
     * Skenario 5 — Jaringan Rekan Tim:
     * Leaving workspace towards Jaringan Rekan Tim requires Admin account password.
     */
    public function test_leaving_workspace_towards_friends_requires_admin_account_password()
    {
        $this->actingAs($this->admin)->post(route('company.year.unlock', 2024), ['password' => 'workspacesecret2024']);
        $group2024 = $this->year2024->monthlyWorkspaces()->first()->group;

        $response = $this->actingAs($this->admin)->postJson(route('groups.leave', $group2024->id), [
            'destination' => 'friends',
            'password' => 'adminaccountpass',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['redirect' => route('dashboard', ['open' => 'friends'])]);
        $this->assertFalse($this->year2024->fresh()->isUnlockedForUser($this->admin->id));
    }

    /**
     * Skenario 6 — Logout:
     * Leaving workspace towards logout requires Admin account password.
     */
    public function test_leaving_workspace_towards_logout_requires_admin_account_password()
    {
        $this->actingAs($this->admin)->post(route('company.year.unlock', 2024), ['password' => 'workspacesecret2024']);
        $group2024 = $this->year2024->monthlyWorkspaces()->first()->group;

        // 1. Wrong password rejects logout
        $wrongResp = $this->actingAs($this->admin)->postJson(route('groups.leave', $group2024->id), [
            'destination' => 'logout',
            'password' => 'wrongpass',
        ]);
        $wrongResp->assertStatus(422);
        $this->assertAuthenticatedAs($this->admin);

        // 2. Correct password executes logout
        $logoutResp = $this->actingAs($this->admin)->postJson(route('groups.leave', $group2024->id), [
            'destination' => 'logout',
            'password' => 'adminaccountpass',
        ]);
        $logoutResp->assertStatus(200);
        $logoutResp->assertJsonFragment(['redirect' => route('login')]);
        $this->assertGuest();
    }

    /**
     * Skenario 7 — Workspace tanpa password:
     * If Owner has not configured password, Admin receives configuration message, not 403.
     */
    public function test_admin_accessing_workspace_without_password_shows_configuration_message()
    {
        $initializer = app(CompanyWorkspaceInitializer::class);
        $year2027 = $initializer->provisionYear(2027, $this->owner->id, false);
        $year2027->password = null;
        $year2027->save();

        // 1. Visiting year page renders configuration required view (200 status, not 403)
        $resp = $this->actingAs($this->admin)->get(route('company.year', 2027));
        $resp->assertStatus(200);

        // 2. Attempting unlock returns 409 configuration_required (not 403)
        $unlockAttempt = $this->actingAs($this->admin)->postJson(route('company.year.unlock', 2027), [
            'password' => 'anypassword',
        ]);
        $unlockAttempt->assertStatus(409);
        $unlockAttempt->assertJsonFragment(['status' => 'configuration_required']);
    }

    /**
     * Skenario 8 — Owner:
     * Owner has unrestricted access without entering any password.
     */
    public function test_owner_has_unrestricted_access_to_all_years_and_months()
    {
        $this->assertTrue($this->year2024->isUnlockedForUser($this->owner->id));
        $this->assertTrue($this->year2025->isUnlockedForUser($this->owner->id));

        $ownerYear = $this->actingAs($this->owner)->get(route('company.year', 2024));
        $ownerYear->assertStatus(200);
        $ownerYear->assertDontSee('Masukkan Password Workspace');

        $ownerMonth = $this->actingAs($this->owner)->get(route('company.workspace', ['year' => 2024, 'month' => 1]));
        $ownerMonth->assertStatus(200);
    }

    /**
     * Strict Security: Admin cannot access archived operational years.
     */
    public function test_admin_cannot_access_archived_operational_year()
    {
        $this->year2024->archive();

        $resp = $this->actingAs($this->admin)->get(route('company.year', 2024));
        $resp->assertStatus(403);
    }
}
