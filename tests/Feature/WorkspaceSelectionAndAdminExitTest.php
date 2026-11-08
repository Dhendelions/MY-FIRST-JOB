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
            'role' => 'owner',
        ]);
        $otherOwner = User::create([
            'name' => 'Other Owner',
            'email' => 'other-owner@example.test',
            'password' => 'password',
            'role' => 'owner',
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
            ->assertDontSee('Katalog Workspace Tim')
            ->assertDontSee('Pilih Semua yang Dapat Dikelola');

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

    public function test_dashboard_catalog_is_removed_without_deleting_workspace_records(): void
    {
        $leader = User::create(['name' => 'Search Owner', 'email' => 'search-owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $visible = $this->createGroup($leader, 'Visible workspace');
        $hidden = $this->createGroup($leader, 'Hidden workspace');

        $this->actingAs($leader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Katalog Workspace Tim')
            ->assertDontSee('Visible workspace')
            ->assertDontSee('Hidden workspace')
            ->assertDontSee('groups.bulk-destroy');

        $this->assertDatabaseHas('fisheries_groups', ['id' => $visible->id]);
        $this->assertDatabaseHas('fisheries_groups', ['id' => $hidden->id]);
    }

    public function test_bulk_delete_reports_unavailable_workspaces_and_deletes_other_selected_workspaces(): void
    {
        $leader = User::create(['name' => 'Partial Owner', 'email' => 'partial-owner@example.test', 'password' => 'password', 'role' => 'owner']);
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

    public function test_admin_dashboard_navigation_invalidates_workspace_authorization(): void
    {
        $leader = User::create(['name' => 'Owner', 'email' => 'exit-owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Workspace Admin', 'email' => 'exit-admin@example.test', 'password' => 'account-secret']);
        $group = $this->createGroup($leader, 'Admin exit workspace', 'exit-ws-pass');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Password workspace salah. Silakan coba lagi.');

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'exit-ws-pass',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet->id]));

        $this->actingAs($admin)
            ->get(route('dashboard', ['open' => 'friends']))
            ->assertOk();
        $this->assertFalse(session()->has('admin_workspace_auth'));

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace')
            ->assertDontSee('spreadsheet-grid-table');

        $this->actingAs($leader)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertDontSee('Masukkan Password Workspace');
    }

    public function test_workspace_header_controls_render_on_each_sheet_for_admin_and_employee(): void
    {
        $leader = User::create(['name' => 'Header Owner', 'email' => 'header-owner@example.test', 'password' => 'password']);
        $admin = User::create(['name' => 'Header Admin', 'email' => 'header-admin@example.test', 'password' => 'password']);
        $employee = User::create(['name' => 'Header Employee', 'email' => 'header-employee@example.test', 'password' => 'password']);
        $group = $this->createGroup($leader, 'Persistent header workspace');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $employee->id, 'role' => 'admin', 'status' => 'Aktif']);
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

    public function test_workspace_data_endpoints_require_active_admin_workspace_authorization(): void
    {
        $leader = User::create(['name' => 'Admin Owner', 'email' => 'admin-owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Admin Member', 'email' => 'workspace-password@example.test', 'password' => 'account-secret']);
        $group = $this->createGroup($leader, 'Admin password workspace', 'ws-secret');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace');

        $this->actingAs($admin)
            ->get(route('spreadsheet.export', $sheet))
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'ws-secret',
            ])
            ->assertOk()
            ->assertJsonPath('redirect', route('group.workspace', [$group->id, $sheet->id]));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
        $this->assertFalse(session()->has('admin_workspace_auth'));

        $this->actingAs($admin)
            ->get(route('spreadsheet.export', $sheet))
            ->assertForbidden();

        $this->actingAs($leader)
            ->get(route('spreadsheet.export', $sheet))
            ->assertOk();
    }

    public function test_switching_workspaces_invalidates_the_previous_workspace_authentication(): void
    {
        $leader = User::create(['name' => 'Multi Owner', 'email' => 'multi-owner@example.test', 'password' => 'password']);
        $admin = User::create(['name' => 'Multi Admin', 'email' => 'multi-admin@example.test', 'password' => 'account-secret']);

        $firstGroup = $this->createGroup($leader, 'First Workspace', 'ws1-secret');
        $secondGroup = $this->createGroup($leader, 'Second Workspace', 'ws2-secret');

        GroupMember::create(['group_id' => $firstGroup->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $secondGroup->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);

        $firstSheet = $firstGroup->sheets()->create(['name' => 'Sheet 1', 'category' => 'analisa']);
        $secondSheet = $secondGroup->sheets()->create(['name' => 'Sheet 2', 'category' => 'analisa']);

        // Unlock first workspace as admin with workspace password
        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $firstGroup), [
                'sheet_id' => $firstSheet->id,
                'password' => 'ws1-secret',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('group.workspace', [$firstGroup->id, $firstSheet->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('group.workspace', [$secondGroup->id, $secondSheet->id]))
            ->assertOk()
            ->assertSee('Masukkan Password Workspace')
            ->assertDontSee('spreadsheet-grid-table');

        $this->assertFalse(session()->has('admin_workspace_auth'));

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $secondGroup), [
                'sheet_id' => $secondSheet->id,
                'password' => 'ws2-secret',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('group.workspace', [$secondGroup->id, $secondSheet->id]))
            ->assertOk()
            ->assertDontSee('Masukkan Password Workspace');
    }

    public function test_logout_invalidates_admin_workspace_authorization_without_an_exit_prompt(): void
    {
        $leader = User::create(['name' => 'Logout Owner', 'email' => 'logout-owner@example.test', 'password' => 'password', 'role' => 'owner']);
        $admin = User::create(['name' => 'Logout Admin', 'email' => 'logout-admin@example.test', 'password' => 'account-secret']);
        $group = $this->createGroup($leader, 'Logout workspace', 'ws-logout-secret');
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);

        $this->actingAs($admin)
            ->postJson(route('groups.unlock', $group), [
                'sheet_id' => $sheet->id,
                'password' => 'ws-logout-secret',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function createGroup(User $owner, string $name, ?string $password = null): FisheriesGroup
    {
        $group = FisheriesGroup::create([
            'name' => $name,
            'password' => $password ? Hash::make($password) : null,
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
