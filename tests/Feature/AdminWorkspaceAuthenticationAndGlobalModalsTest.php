<?php

namespace Tests\Feature;

use App\Models\OperationalYear;
use App\Models\User;
use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminWorkspaceAuthenticationAndGlobalModalsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private OperationalYear $year2026;
    private OperationalYear $year2025;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner Dadhen',
            'email' => 'owner_modal_test@test.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Dadhen',
            'email' => 'admin_modal_test@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);

        $initializer = app(CompanyWorkspaceInitializer::class);
        $this->year2026 = $initializer->provisionYear(2026, $this->owner->id, false);
        $this->year2025 = $initializer->provisionYear(2025, $this->owner->id, false);

        $this->year2026->password = Hash::make('SecretYear2026');
        $this->year2026->password_version = 1;
        $this->year2026->save();

        $this->year2025->password = Hash::make('SecretYear2025');
        $this->year2025->password_version = 1;
        $this->year2025->save();
    }

    /**
     * Requirements 1, 2, 3: Admin enters correct password and accesses workspace without 403.
     */
    public function test_admin_enters_correct_password_and_accesses_workspace_without_403(): void
    {
        $this->actingAs($this->admin);

        // 1. Visit year page when locked
        $yearPage = $this->get(route('company.year', 2026));
        $yearPage->assertOk();
        $yearPage->assertSee('Masukkan Password Workspace 2026');

        // 2. Submit correct password
        $unlockResponse = $this->post(route('company.year.unlock', 2026), [
            'password' => 'SecretYear2026',
        ]);
        $unlockResponse->assertRedirect(route('company.year', 2026));

        // 3. User can now view year overview with 12 operational months
        $unlockedYear = $this->get(route('company.year', 2026));
        $unlockedYear->assertOk();
        $unlockedYear->assertSee('Daftar Bulan Operasional');
        $unlockedYear->assertSee('Januari');
    }

    /**
     * Requirements 4 & 5: Admin opens Januari, switches between months without 403.
     */
    public function test_admin_accesses_januari_and_switches_months_without_403_or_reprompt(): void
    {
        $this->actingAs($this->admin);

        // Unlock year
        $this->post(route('company.year.unlock', 2026), [
            'password' => 'SecretYear2026',
        ])->assertRedirect();

        // 4. Open month Januari (month 1)
        $janResponse = $this->get(route('company.workspace', [2026, 1]));
        $janResponse->assertOk();
        $janResponse->assertViewHas('requiresPasswordUnlock', false);

        // Open sheet Analisa in Januari
        $analisaResponse = $this->get(route('company.workspace', [2026, 1, 'Analisa']));
        $analisaResponse->assertOk();
        $analisaResponse->assertViewHas('requiresPasswordUnlock', false);

        // Open sheet Cost in Januari
        $costResponse = $this->get(route('company.workspace', [2026, 1, 'Cost']));
        $costResponse->assertOk();
        $costResponse->assertViewHas('requiresPasswordUnlock', false);

        // 5. Switch to Februari (month 2) in the same year without 403 or password prompt
        $febResponse = $this->get(route('company.workspace', [2026, 2]));
        $febResponse->assertOk();
        $febResponse->assertViewHas('requiresPasswordUnlock', false);

        // Switch to Maret (month 3)
        $marResponse = $this->get(route('company.workspace', [2026, 3]));
        $marResponse->assertOk();
        $marResponse->assertViewHas('requiresPasswordUnlock', false);

        // Switch to Desember (month 12)
        $decResponse = $this->get(route('company.workspace', [2026, 12]));
        $decResponse->assertOk();
        $decResponse->assertViewHas('requiresPasswordUnlock', false);
    }

    /**
     * Requirements 6 & 7: Returning to dashboard clears session, re-entry requires password.
     */
    public function test_returning_to_dashboard_resets_auth_and_reentry_requires_password(): void
    {
        $this->actingAs($this->admin);

        // Authenticate Year 2026
        $this->post(route('company.year.unlock', 2026), [
            'password' => 'SecretYear2026',
        ])->assertRedirect();

        // Admin can access Month 1
        $this->get(route('company.workspace', [2026, 1]))->assertOk();

        // 6. Return to Dashboard / Beranda
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertOk();

        // Session authentication for Year 2026 is cleared
        $this->assertFalse($this->year2026->isUnlockedForUser($this->admin->id));

        // 7. Visiting Year 2026 again requires password prompt
        $yearAgain = $this->get(route('company.year', 2026));
        $yearAgain->assertOk();
        $yearAgain->assertSee('Masukkan Password Workspace 2026');

        // A browser navigation returns to the canonical password gate. Data
        // endpoints remain forbidden; this avoids a raw 403 screen.
        $this->get(route('company.workspace', [2026, 1]))
            ->assertRedirect(route('company.year', 2026));
    }

    /**
     * Requirement 8: Wrong password is rejected with error.
     */
    public function test_wrong_password_is_rejected(): void
    {
        $this->actingAs($this->admin);

        // Form submit with wrong password redirects back with error
        $response = $this->post(route('company.year.unlock', 2026), [
            'password' => 'WrongPassword123',
        ]);
        $response->assertSessionHas('error');
        $this->assertFalse($this->year2026->isUnlockedForUser($this->admin->id));

        // JSON submit with wrong password returns 422
        $jsonResponse = $this->postJson(route('company.year.unlock', 2026), [
            'password' => 'WrongPassword123',
        ]);
        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 9: Owner accesses workspace without password.
     */
    public function test_owner_accesses_workspace_and_all_months_without_password(): void
    {
        $this->actingAs($this->owner);

        // Year overview
        $yearResponse = $this->get(route('company.year', 2026));
        $yearResponse->assertOk();
        $yearResponse->assertSee('Daftar Bulan Operasional');
        $yearResponse->assertDontSee('Masukkan Password Workspace 2026');

        // Month 1
        $janResponse = $this->get(route('company.workspace', [2026, 1]));
        $janResponse->assertOk();
        $janResponse->assertViewHas('requiresPasswordUnlock', false);

        // Month 12
        $decResponse = $this->get(route('company.workspace', [2026, 12]));
        $decResponse->assertOk();
        $decResponse->assertViewHas('requiresPasswordUnlock', false);

        // Restricted sheet Margin
        $marginResponse = $this->get(route('company.workspace', [2026, 1, 'Margin']));
        $marginResponse->assertOk();
    }

    /**
     * Requirement 10: Global fullscreen modals are present in the application layout.
     */
    public function test_global_fullscreen_modals_render_in_layout(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // 1. Global Unlock Password Modal
        $response->assertSee('globalUnlockModal.show', false);
        $response->assertSee('global-unlock-title', false);
        $response->assertSee('global_unlock_password_input', false);
        $response->assertSee('z-[9999]', false);

        // 2. Global Initiation Workspace Modal
        $response->assertSee('globalInitiateModal.show', false);
        $response->assertSee('global-initiate-title', false);
        $response->assertSee('global_initiate_year_input', false);
    }

    /**
     * Requirement 11: Direct browser navigation without password reaches the
     * canonical password gate rather than exposing workspace data.
     */
    public function test_direct_url_access_without_password_redirects_to_password_gate(): void
    {
        $this->actingAs($this->admin);

        // Direct month URL must not render data and must not show a raw 403.
        $response = $this->get(route('company.workspace', [2026, 1]));
        $response->assertRedirect(route('company.year', 2026));

        $responseMonth2 = $this->get(route('company.workspace', [2026, 2]));
        $responseMonth2->assertRedirect(route('company.year', 2026));
    }
}
