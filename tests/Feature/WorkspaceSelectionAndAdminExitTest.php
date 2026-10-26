<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetActivity;
use App\Models\SpreadsheetCell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkspaceSelectionAndAdminExitTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_bulk_delete_owned_workspaces_and_cascades_only_their_data(): void
    {
        $leader = User::create([
            'name' => 'Workspace Owner',
            'email' => 'bulk-owner@example.test',
            'password' => 'password',
        ]);
        $otherOwner = User::create([
            'name' => 'Other Owner',
            'email' => 'other-owner@example.test',
            'password' => 'password',
        ]);

        $first = $this->createGroup($leader, 'First workspace');
        $second = $this->createGroup($leader, 'Second workspace');
        $untouched = $this->createGroup($otherOwner, 'Untouched workspace');
        GroupMember::create([
            'group_id' => $untouched->id,
            'user_id' => $leader->id,
            'role' => 'admin',
            'status' => 'Aktif',
        ]);

        $sheet = $first->sheets()->create(['name' => 'Data']);
        $column = $sheet->columns()->create(['letter' => 'A', 'title' => 'Nilai', 'sort_order' => 1]);
        $row = $sheet->rows()->create(['sort_order' => 1]);
        $cell = SpreadsheetCell::create(['row_id' => $row->id, 'column_id' => $column->id, 'value' => '=1+1']);
        SpreadsheetActivity::create([
            'event_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'group_id' => $first->id,
            'sheet_id' => $sheet->id,
            'user_id' => $leader->id,
            'action' => 'cell_updated',
            'committed_at' => now(),
        ]);
        $untouchedSheet = $untouched->sheets()->create(['name' => 'Retained']);

        $this->actingAs($leader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pilih Semua yang Dapat Dikelola')
            ->assertSee('aria-label="Pilih workspace First workspace"', false)
            ->assertDontSee('aria-label="Pilih workspace Untouched workspace"', false);

        $this->actingAs($leader)
            ->post(route('groups.bulk-destroy'), ['groups' => [$first->id, $second->id]])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertDatabaseMissing('group_members', ['group_id' => $first->id]);
        $this->assertDatabaseMissing('spreadsheet_sheets', ['id' => $sheet->id]);
        $this->assertDatabaseMissing('spreadsheet_rows', ['id' => $row->id]);
        $this->assertDatabaseMissing('spreadsheet_columns', ['id' => $column->id]);
        $this->assertDatabaseMissing('spreadsheet_cells', ['id' => $cell->id]);
        $this->assertDatabaseMissing('spreadsheet_activities', ['group_id' => $first->id]);
        $this->assertDatabaseHas('fisheries_groups', ['id' => $untouched->id]);
        $this->assertDatabaseHas('spreadsheet_sheets', ['id' => $untouchedSheet->id]);
        $this->assertDatabaseHas('users', ['id' => $leader->id]);
    }

    public function test_dashboard_bulk_selection_only_includes_manageable_workspaces_matching_search(): void
    {
        $leader = User::create(['name' => 'Search Owner', 'email' => 'search-owner@example.test', 'password' => 'password']);
        $visible = $this->createGroup($leader, 'Visible workspace');
        $hidden = $this->createGroup($leader, 'Hidden workspace');

        $response = $this->actingAs($leader)
            ->get(route('dashboard', ['q' => 'Visible']))
            ->assertOk()
            ->assertSee('aria-label="Pilih workspace Visible workspace"', false)
            ->assertDontSee('aria-label="Pilih workspace Hidden workspace"', false)
            ->assertSee('Pilih Semua yang Dapat Dikelola')
            ->assertSee('Batalkan Pilihan')
            ->assertSee('submitBulkDelete($event)');

        preg_match('/manageableWorkspaces: (.+?),\s*selectedWorkspaceIds:/s', $response->getContent(), $selectionState);
        $this->assertNotEmpty($selectionState);
        $this->assertStringContainsString('Visible workspace', $selectionState[1]);
        $this->assertStringNotContainsString('Hidden workspace', $selectionState[1]);
        $this->assertDatabaseHas('fisheries_groups', ['id' => $visible->id]);
        $this->assertDatabaseHas('fisheries_groups', ['id' => $hidden->id]);
    }

    public function test_bulk_delete_reports_unavailable_workspaces_and_deletes_other_selected_workspaces(): void
    {
        $leader = User::create(['name' => 'Partial Owner', 'email' => 'partial-owner@example.test', 'password' => 'password']);
        $selected = $this->createGroup($leader, 'Available workspace');
        $untouched = $this->createGroup($leader, 'Not selected workspace');

        $this->actingAs($leader)
            ->post(route('groups.bulk-destroy'), ['groups' => [$selected->id, 999999]])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', function (string $message): bool {
                return str_contains($message, '1 workspace berhasil dihapus')
                    && str_contains($message, 'Workspace #999999 (sudah tidak tersedia)');
            });

        $this->assertModelMissing($selected);
        $this->assertDatabaseHas('fisheries_groups', ['id' => $untouched->id]);
    }

    public function test_bulk_delete_authorizes_every_existing_workspace_before_deleting_any(): void
    {
        $leader = User::create(['name' => 'Bulk Owner', 'email' => 'bulk-auth-owner@example.test', 'password' => 'password']);
        $otherOwner = User::create(['name' => 'Other Owner', 'email' => 'bulk-auth-other@example.test', 'password' => 'password']);
        $ownWorkspace = $this->createGroup($leader, 'Owned workspace');
        $managedByAnother = $this->createGroup($otherOwner, 'Admin workspace');
        GroupMember::create([
            'group_id' => $managedByAnother->id,
            'user_id' => $leader->id,
            'role' => 'admin',
            'status' => 'Aktif',
        ]);

        $this->actingAs($leader)
            ->post(route('groups.bulk-destroy'), ['groups' => [$ownWorkspace->id, $managedByAnother->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('fisheries_groups', ['id' => $ownWorkspace->id]);
        $this->assertDatabaseHas('fisheries_groups', ['id' => $managedByAnother->id]);
    }

    public function test_admin_cannot_delete_a_workspace_through_a_forged_bulk_request(): void
    {
        $leader = User::create(['name' => 'Owner', 'email' => 'bulk-leader@example.test', 'password' => 'password']);
        $admin = User::create(['name' => 'Admin', 'email' => 'bulk-admin@example.test', 'password' => 'password']);
        $group = $this->createGroup($leader, 'Admin-managed workspace');
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'Aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pilih Semua yang Dapat Dikelola')
            ->assertDontSee('aria-label="Pilih workspace', false);

        $this->actingAs($admin)
            ->post(route('groups.bulk-destroy'), ['groups' => [$group->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('fisheries_groups', ['id' => $group->id]);
    }

    public function test_admin_must_reauthenticate_to_leave_and_direct_navigation_is_guarded(): void
    {
        $leader = User::create(['name' => 'Owner', 'email' => 'exit-owner@example.test', 'password' => 'password']);
        $admin = User::create([
            'name' => 'Workspace Admin',
            'email' => 'exit-admin@example.test',
            'password' => Hash::make('account-secret'),
        ]);
        $employee = User::create(['name' => 'Employee', 'email' => 'exit-employee@example.test', 'password' => 'password']);
        $group = $this->createGroup($leader, 'Admin exit workspace');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $employee->id, 'role' => 'karyawan', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Verifikasi Password Akun');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'wrong-account-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Password akun salah. Silakan coba lagi.');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'account-secret',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet->id]));

        $this->actingAs($admin)
            ->get(route('dashboard', ['open' => 'friends']))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', '/dashboard?open=friends');

        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'wrong-password',
                'destination' => '/dashboard?open=friends',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue(session()->has('admin_workspace_context'));

        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => 'https://example.test/',
            ])
            ->assertStatus(422);
        $this->assertTrue(session()->has('admin_workspace_context'));

        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => '/dashboard?open=friends',
            ])
            ->assertRedirect('/dashboard?open=friends');
        $this->assertFalse(session()->has('admin_workspace_context'));

        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => '/dashboard',
            ])
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertDontSee('Verifikasi Password Akun');
    }

    public function test_workspace_header_controls_render_on_each_sheet_for_admin_and_employee(): void
    {
        $leader = User::create(['name' => 'Header Owner', 'email' => 'header-owner@example.test', 'password' => 'password']);
        $admin = User::create(['name' => 'Header Admin', 'email' => 'header-admin@example.test', 'password' => 'password']);
        $employee = User::create(['name' => 'Header Employee', 'email' => 'header-employee@example.test', 'password' => 'password']);
        $group = $this->createGroup($leader, 'Persistent header workspace');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $employee->id, 'role' => 'karyawan', 'status' => 'Aktif']);
        $sheets = collect([
            $group->sheets()->create(['name' => 'First sheet', 'category' => 'analisa']),
            $group->sheets()->create(['name' => 'Second sheet', 'category' => 'cost']),
        ]);

        foreach ([$admin, $employee] as $user) {
            foreach ($sheets as $sheet) {
                $request = $this->actingAs($user);
                if ($user->is($admin)) {
                    $request->withSession([$group->adminVerificationSessionKey($admin->id) => $sheet->id]);
                }

                $request->get(route('group.workspace', [$group->id, $sheet->id]))
                    ->assertOk()
                    ->assertSee('Peran Anda:')
                    ->assertSee('Persistent header workspace')
                    ->assertSee('Riwayat Aktivitas')
                    ->assertSee('title="Kelola Anggota & Peran"', false)
                    ->assertSee('sticky top-0 z-30', false);
            }
        }
    }

    public function test_employee_must_reauthenticate_with_account_password_to_leave_workspace(): void
    {
        $leader = User::create(['name' => 'Employee Owner', 'email' => 'employee-owner@example.test', 'password' => 'password']);
        $employee = User::create([
            'name' => 'Employee',
            'email' => 'workspace-password@example.test',
            'password' => Hash::make('account-secret'),
        ]);
        $group = $this->createGroup($leader, 'Employee password workspace');
        $group->update(['password' => Hash::make('workspace-secret')]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $employee->id, 'role' => 'karyawan', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($employee)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace');

        $this->actingAs($employee)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Password workspace salah.');

        $this->actingAs($employee)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'workspace-secret',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet->id]));

        // Direct navigation to dashboard is guarded
        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', '/dashboard');

        // Wrong password leaves user in workspace
        $this->actingAs($employee)
            ->post(route('groups.leave', $group), [
                'password' => 'wrong-account-password',
                'destination' => '/dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue(session()->has('admin_workspace_context'));

        // Correct account password allows exit to destination
        $this->actingAs($employee)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => '/dashboard',
            ])
            ->assertRedirect('/dashboard');
        $this->assertFalse(session()->has('admin_workspace_context'));

        // Re-entering and leaving again requires password again
        $this->actingAs($employee)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'workspace-secret',
            ])
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', '/dashboard');
    }

    public function test_user_navigating_directly_to_another_workspace_must_reauthenticate_previous_workspace_first(): void
    {
        $leader = User::create(['name' => 'Multi Owner', 'email' => 'multi-owner@example.test', 'password' => 'password']);
        $employee = User::create([
            'name' => 'Multi Employee',
            'email' => 'multi-employee@example.test',
            'password' => Hash::make('account-secret'),
        ]);

        $firstGroup = $this->createGroup($leader, 'First Workspace');
        $secondGroup = $this->createGroup($leader, 'Second Workspace');

        GroupMember::create(['group_id' => $firstGroup->id, 'user_id' => $employee->id, 'role' => 'karyawan', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $secondGroup->id, 'user_id' => $employee->id, 'role' => 'karyawan', 'status' => 'Aktif']);

        $firstSheet = $firstGroup->sheets()->create(['name' => 'Sheet 1', 'category' => 'analisa']);
        $secondSheet = $secondGroup->sheets()->create(['name' => 'Sheet 2', 'category' => 'analisa']);

        // Enter first workspace
        $this->actingAs($employee)
            ->get(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertOk();

        // Attempting to visit second workspace directly is guarded by first workspace
        $targetPath = "/groups/{$secondGroup->id}/workspace/{$secondSheet->id}";
        $this->actingAs($employee)
            ->get($targetPath)
            ->assertRedirect(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertSessionHas('workspace_exit_target', $targetPath);

        // Verify with account password to leave first workspace
        $this->actingAs($employee)
            ->post(route('groups.leave', $firstGroup), [
                'password' => 'account-secret',
                'destination' => $targetPath,
            ])
            ->assertRedirect($targetPath);

        $this->assertFalse(session()->has('admin_workspace_context'));
    }

    public function test_admin_and_employee_logout_from_workspace_requires_account_password(): void
    {
        $leader = User::create(['name' => 'Logout Owner', 'email' => 'logout-owner@example.test', 'password' => 'password']);
        $admin = User::create([
            'name' => 'Logout Admin',
            'email' => 'logout-admin@example.test',
            'password' => Hash::make('account-secret'),
        ]);
        $group = $this->createGroup($leader, 'Logout workspace');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'account-secret',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('group.workspace', [$group->id, $sheet->id]))
            ->assertSessionHas('workspace_exit_target', 'logout');

        $this->actingAs($admin)
            ->post(route('groups.leave', $group), [
                'password' => 'account-secret',
                'destination' => 'logout',
            ])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function createGroup(User $owner, string $name): FisheriesGroup
    {
        $group = FisheriesGroup::create([
            'name' => $name,
            'invite_code' => strtoupper(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
            'created_by' => $owner->id,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => 'leader',
            'status' => 'Aktif',
        ]);

        return $group;
    }
}
