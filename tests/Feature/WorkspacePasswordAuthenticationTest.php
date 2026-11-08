<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkspacePasswordAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspaceWithSheets(User $creator, string $workspacePassword = 'secret-workspace-pass'): array
    {
        $group = FisheriesGroup::create([
            'name' => 'Secured Workspace',
            'password' => Hash::make($workspacePassword),
            'category' => 'Umum',
            'location' => 'Utama',
            'pond_count' => 5,
            'invite_code' => 'SEC-WS-01',
            'created_by' => $creator->id,
        ]);

        $sheet1 = SpreadsheetSheet::create([
            'group_id' => $group->id,
            'name' => 'Analisa',
            'category' => 'Analisa',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $sheet1->initializeBlankGrid();

        $sheet2 = SpreadsheetSheet::create([
            'group_id' => $group->id,
            'name' => 'Cost',
            'category' => 'Cost',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $sheet2->initializeBlankGrid();

        return [$group, $sheet1, $sheet2];
    }

    public function test_correct_workspace_password_grants_access(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $member = User::create(['name' => 'Member User', 'email' => 'member@example.test', 'password' => 'password', 'role' => 'admin']);

        [$group, $sheet1] = $this->createWorkspaceWithSheets($owner, 'valid-pass-123');
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'admin',
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

        // Wrong password fails with 422 JSON
        $this->actingAs($member)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet1->id,
                'password' => 'wrong-pass',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Password workspace salah. Silakan coba lagi.');

        // Correct workspace password succeeds and returns redirect URL
        $this->actingAs($member)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet1->id,
                'password' => 'valid-pass-123',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet1->id]));
    }

    public function test_admin_is_authenticated_once_per_workspace_and_dashboard_exit_clears_access(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@example.test', 'password' => 'admin-secure-pass', 'role' => 'admin']);

        [$group, $analisa, $cost] = $this->createWorkspaceWithSheets($owner, 'ws-pass');
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

        // 1. Verify workspace with workspace password
        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $analisa->id,
                'password' => 'ws-pass',
            ])
            ->assertOk();

        $this->assertSame(
            ['user_id' => $admin->id, 'group_id' => $group->id, 'role' => 'admin'],
            session('admin_workspace_auth')
        );

        // Sheet navigation within the same workspace stays authorized.
        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $analisa->id]))
            ->assertOk()
            ->assertDontSee('Masukkan Password Workspace');

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $cost->id]))
            ->assertOk()
            ->assertDontSee('Masukkan Password Workspace');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
        $this->assertFalse(session()->has('admin_workspace_auth'));

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $analisa->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace')
            ->assertDontSee('spreadsheet-grid-table');
    }

    public function test_direct_url_access_still_enforces_authentication(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@example.test', 'password' => 'admin-secure-pass', 'role' => 'admin']);

        [$group, $analisa] = $this->createWorkspaceWithSheets($owner, 'ws-pass');
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

        // Direct GET without entering password prompts for password
        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $analisa->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace')
            ->assertDontSee('spreadsheet-grid-table');

        $this->actingAs($admin)
            ->get(route('spreadsheet.export', $analisa))
            ->assertForbidden();
    }

    public function test_clearing_session_forces_reauthentication(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@example.test', 'password' => 'admin-secure-pass', 'role' => 'admin']);

        [$group, $analisa] = $this->createWorkspaceWithSheets($owner, 'ws-pass');
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

        // Simulating cleared session (no unlock key)
        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $analisa->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace')
            ->assertDontSee('spreadsheet-grid-table');
    }

    public function test_admin_cannot_enter_a_workspace_until_a_password_is_configured(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@example.test', 'password' => 'password', 'role' => 'admin']);
        $group = FisheriesGroup::create([
            'name' => 'Workspace Without Password',
            'category' => 'Umum',
            'location' => 'Utama',
            'invite_code' => 'NO-PASS-01',
            'created_by' => $owner->id,
        ]);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Password Workspace Belum Diatur')
            ->assertDontSee('spreadsheet-grid-table');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'arbitrary-password',
            ])
            ->assertStatus(409);

        $this->actingAs($admin)
            ->get(route('spreadsheet.export', $sheet))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertDontSee('Masukkan Password Workspace');
    }
}
