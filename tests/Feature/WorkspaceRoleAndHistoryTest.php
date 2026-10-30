<?php

namespace Tests\Feature;

use App\Livewire\SpreadsheetWorksheet;
use App\Livewire\WorkspaceActivityHistory;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetActivity;
use App\Models\SpreadsheetCell;
use App\Models\User;
use App\Services\OktAnalisaTemplateImporter;
use App\Services\SpreadsheetFormulaEngine;
use App\Services\SpreadsheetWorkbookCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceRoleAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_cleans_placeholder_and_retains_action_buttons(): void
    {
        $user = User::create([
            'name' => 'Clean User',
            'email' => 'clean@example.test',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        // 1. Placeholder "Belum ada workspace" and "+ Buat Workspace" in sidebar are removed
        $response->assertDontSee('text-[11px] text-slate-400">Belum ada workspace</p>', false);
        $response->assertDontSee('+ Buat Workspace</button>', false);

        // 2. "Buat Baru" and "Gabung" buttons are maintained
        $response->assertSee('Buat Baru');
        $response->assertSee('Gabung');
    }

    public function test_user_search_and_add_member_by_name_and_role(): void
    {
        $leader = User::create(['name' => 'Leader Budi', 'email' => 'leader@example.test', 'password' => 'password']);
        $candidate1 = User::create(['name' => 'Andi Wijaya', 'email' => 'andi@example.test', 'password' => 'password', 'role_title' => 'Teknisi Tambak']);
        $candidate2 = User::create(['name' => 'Andi Pratama', 'email' => 'andi2@example.test', 'password' => 'password', 'role_title' => 'Staff Kolam']);

        $group = FisheriesGroup::create([
            'name' => 'Workspace Tambak',
            'invite_code' => 'WS-TAMBAK-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        // Search users by name
        $searchRes = $this->actingAs($leader)
            ->get(route('groups.users.search', [$group->id, 'name' => 'Andi']));
        $searchRes->assertOk();
        $usersData = $searchRes->json('users');
        $this->assertCount(2, $usersData);
        $this->assertSame('Andi Wijaya', $usersData[0]['name']);
        $this->assertFalse($usersData[0]['is_member']);

        // Add member using unique user_id and role
        $addRes = $this->actingAs($leader)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $candidate1->id,
                'role' => 'admin',
            ]);
        $addRes->assertRedirect();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $candidate1->id,
            'role' => 'admin',
            'status' => 'Aktif',
        ]);

        // Duplicate addition should be prevented
        $duplicateRes = $this->actingAs($leader)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $candidate1->id,
                'role' => 'admin',
            ]);
        $duplicateRes->assertSessionHas('error');
    }

    public function test_role_permission_restrictions_on_sheets_and_api(): void
    {
        $leader = User::create(['name' => 'Leader Master', 'email' => 'master@example.test', 'password' => 'password']);
        $admin = User::create(['name' => 'Admin Alex', 'email' => 'admin@example.test', 'password' => 'password']);
        $karyawan = User::create(['name' => 'Staff Dika', 'email' => 'staff@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'Workspace Multi Role',
            'invite_code' => 'WS-ROLE-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $karyawan->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Create standard named sheets: Analisa, Cost, Margin, Pendukung, Rank
        $analisa = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $analisa->initializeBlankGrid();
        $cost = $group->sheets()->create(['name' => 'Cost', 'category' => 'cost']);
        $cost->initializeBlankGrid();
        $margin = $group->sheets()->create(['name' => 'Margin', 'category' => 'margin']);
        $margin->initializeBlankGrid();
        $pendukung = $group->sheets()->create(['name' => 'Pendukung', 'category' => 'pendukung']);
        $pendukung->initializeBlankGrid();
        $rank = $group->sheets()->create(['name' => 'Rank', 'category' => 'rank']);
        $rank->initializeBlankGrid();

        // 1. Analisa & Cost: Leader, Admin, Karyawan can access
        $outsider = User::create(['name' => 'Outsider', 'email' => 'out_add@example.test', 'password' => 'password']);
        $this->actingAs($outsider)->get(route('group.workspace', [$group->id, $analisa->id]))->assertOk();
        $this->actingAs($admin)->get(route('group.workspace', [$group->id, $cost->id]))->assertOk();
        $this->actingAs($leader)->get(route('group.workspace', [$group->id, $analisa->id]))->assertOk();

        // 2. Margin, Pendukung, Rank: Admin & Karyawan are RESTRICTED
        $resKaryawanMargin = $this->actingAs($karyawan)->get(route('group.workspace', [$group->id, $margin->id]));
        $resKaryawanMargin->assertOk();
        $resKaryawanMargin->assertSee('Akses Lembar Kerja Dibatasi');
        // Ensure sensitive grid component is NOT rendered
        $resKaryawanMargin->assertDontSee('spreadsheet-grid-table');

        // Backend API check: export should be forbidden (403) for non-leader on restricted sheet
        $outsider = User::create(['name' => 'Outsider', 'email' => 'out_add@example.test', 'password' => 'password']);
        $this->actingAs($outsider)->get(route('spreadsheet.export', $margin))->assertForbidden();
        $this->actingAs($admin)->get(route('spreadsheet.export', $pendukung))->assertForbidden();
        $outsider = User::create(['name' => 'Outsider', 'email' => 'out_add@example.test', 'password' => 'password']);
        $this->actingAs($outsider)->get(route('spreadsheet.export', $rank))->assertForbidden();

        // Livewire access authorization check
        Livewire::actingAs($karyawan)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $margin])
            ->assertForbidden();

        // 3. Leader has full access to Margin, Pendukung, Rank
        $this->actingAs($leader)->get(route('group.workspace', [$group->id, $margin->id]))->assertOk();
        $this->actingAs($leader)->get(route('spreadsheet.export', $margin))->assertOk();
        Livewire::actingAs($leader)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $margin])
            ->assertOk();

        // 4. Karyawan cannot add members
        $outsider = User::create(['name' => 'Outsider', 'email' => 'out_add@example.test', 'password' => 'password']);
        $this->actingAs($outsider)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $leader->id,
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    public function test_leader_sheet_switching_without_password_prompt(): void
    {
        $leader = User::create(['name' => 'Password Leader', 'email' => 'pwleader@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Secured Workspace',
            'invite_code' => 'WS-SEC-01',
            'password' => Hash::make('secret123'),
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);

        $analisa = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $analisa->initializeBlankGrid();
        $cost = $group->sheets()->create(['name' => 'Cost', 'category' => 'cost']);
        $cost->initializeBlankGrid();

        // Unlock once for session
        $this->actingAs($leader)
            ->withSession(['workspace_unlocked_' . $group->id => true])
            ->get(route('group.workspace', [$group->id, $cost->id]))
            ->assertOk()
            ->assertSee('x-teleport="body"', false)
            ->assertSee('workspace-modal-layer')
            ->assertDontSee('Masukkan Password Workspace');
    }

    public function test_admin_must_verify_account_password_for_each_protected_sheet(): void
    {
        $leader = User::create(['name' => 'Owner', 'email' => 'owner-admin-lock@example.test', 'password' => 'password']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-lock@example.test', 'password' => 'akun-admin-aman']);
        $group = FisheriesGroup::create([
            'name' => 'Admin Protected',
            'invite_code' => 'WS-ADMIN-LOCK',
            'password' => Hash::make('workspace-secret'),
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $analisa = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $analisa->initializeBlankGrid();
        $cost = $group->sheets()->create(['name' => 'Cost', 'category' => 'cost']);
        $cost->initializeBlankGrid();

        $this->actingAs($admin)
            ->get(route('group.workspace', [$group->id, $analisa->id]))
            ->assertOk()
            ->assertSee('Verifikasi Password Akun')
            ->assertDontSee('spreadsheet-grid-table');

        $this->actingAs($admin)
            ->post(route('groups.unlock', $group), ['sheet_id' => $analisa->id, 'password' => 'workspace-secret'])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('groups.unlock', $group), ['sheet_id' => $analisa->id, 'password' => 'akun-admin-aman'])
            ->assertRedirect(route('group.workspace', [$group->id, $analisa->id]));

        $this->actingAs($admin)
            ->withSession([$group->adminVerificationSessionKey($admin->id) => $analisa->id])
            ->get(route('spreadsheet.export', $analisa))
            ->assertOk();

        // The grant is scoped to Analisa only, so Cost cannot be fetched
        // through either the page or direct export endpoint without reauth.
        $this->actingAs($admin)
            ->withSession([$group->adminVerificationSessionKey($admin->id) => $analisa->id])
            ->get(route('group.workspace', [$group->id, $cost->id]))
            ->assertOk()
            ->assertSee('Verifikasi Password Akun')
            ->assertDontSee('spreadsheet-grid-table');
        $this->actingAs($admin)
            ->withSession([$group->adminVerificationSessionKey($admin->id) => $analisa->id])
            ->get(route('spreadsheet.export', $cost))
            ->assertForbidden();
    }

    public function test_activity_history_records_events_and_displays_in_asia_jakarta(): void
    {
        $leader = User::create(['name' => 'History Leader', 'email' => 'histleader@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'History Workspace',
            'invite_code' => 'WS-HIST-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();

        $col = $sheet->columns()->first();
        $row = $sheet->rows()->first();

        // 1. Edit cell via Livewire
        Livewire::actingAs($leader)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $row->id, $col->id, '95000', 0);

        // Verify record in database
        $this->assertDatabaseHas('spreadsheet_activities', [
            'group_id' => $group->id,
            'sheet_id' => $sheet->id,
            'user_id' => $leader->id,
            'action' => 'cell_changed',
            'new_value' => '95000',
        ]);

        // 2. Render WorkspaceActivityHistory Livewire component
        $component = Livewire::actingAs($leader)
            ->test(WorkspaceActivityHistory::class, ['groupId' => $group->id]);

        $component->assertSee('History Leader');
        $component->assertSee('Edit Sel');
        $component->assertSee('95000');
        $component->assertSee('WIB');
        $this->assertStringContainsString('wire:poll.30s.visible', $component->html());

        $secondColumn = $sheet->columns()->orderBy('sort_order')->skip(1)->firstOrFail();
        Livewire::actingAs($leader)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $row->id, $secondColumn->id, 'Updated while open', 0);

        $component->call('refreshActivities')->call('refreshActivities');
        $component->assertSee('Updated while open');
        $this->assertCount(2, $component->get('activityIds'));
    }

    public function test_formula_division_by_zero_safe_handling(): void
    {
        $grid = [
            'A1' => '100',
            'B1' => '0',
            'C1' => '',
        ];

        // Should evaluate to 0 instead of uncontrolled #DIV/0!
        $this->assertSame('0', SpreadsheetFormulaEngine::evaluate('=A1/B1', $grid));
        $this->assertSame('0', SpreadsheetFormulaEngine::evaluate('=A1/C1', $grid));
    }

    public function test_workspace_avatar_upload_and_display(): void
    {
        $leader = User::create(['name' => 'Avatar Owner', 'email' => 'avatar@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Avatar Workspace',
            'invite_code' => 'WS-AVATAR-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();

        // 1x1 valid PNG binary
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $fakeImage = UploadedFile::fake()->createWithContent('workspace_logo.png', $pngContent);

        $response = $this->actingAs($leader)
            ->put(route('groups.update', $group->id), [
                'name' => 'Avatar Workspace Updated',
                'avatar' => $fakeImage,
            ]);

        $response->assertRedirect();
        $group->refresh();

        $this->assertNotNull($group->avatar);
        $this->assertStringContainsString('uploads/workspaces', $group->avatar);

        $secondSheet = $group->sheets()->create(['name' => 'Cost', 'category' => 'cost']);
        $secondSheet->initializeBlankGrid();
        $avatarUrl = $group->fresh()->avatar_url;

        $this->actingAs($leader)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertSee($avatarUrl, false);
        $this->actingAs($leader)
            ->get(route('group.workspace', [$group->id, $secondSheet->id]))
            ->assertOk()
            ->assertSee($avatarUrl, false);
    }

    public function test_workspace_view_renders_cleanly_without_raw_javascript_leakage(): void
    {
        $leader = User::create(['name' => 'Render Master', 'email' => 'render@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Clean Render Workspace',
            'invite_code' => 'WS-CLEAN-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();

        $response = $this->actingAs($leader)->get(route('group.workspace', $group->id));
        $response->assertOk();

        // Must NOT leak JavaScript code into HTML markup attributes
        $content = $response->getContent();
        $this->assertStringNotContainsString('x-data="{ members: [', $content);
        $this->assertStringNotContainsString('x-data="{members:[', $content);

        // Must contain clean manager function call and modal buttons
        $response->assertSee('workspaceMembersManager(', false);
        $response->assertSee('Kelola Anggota');
        $response->assertSee('Backup Spreadsheet');
    }

    public function test_workspace_supports_multi_leader_and_multi_admin(): void
    {
        // Scenarios 1, 2, 3: Multi-Leader & Multi-Admin without quantity limits
        $founder = User::create(['name' => 'Founder User', 'email' => 'founder@example.test', 'password' => 'password']);
        $leader2 = User::create(['name' => 'Leader Two', 'email' => 'lead2@example.test', 'password' => 'password']);
        $leader3 = User::create(['name' => 'Leader Three', 'email' => 'lead3@example.test', 'password' => 'password']);
        $admin1  = User::create(['name' => 'Admin One', 'email' => 'adm1@example.test', 'password' => 'password']);
        $admin2  = User::create(['name' => 'Admin Two', 'email' => 'adm2@example.test', 'password' => 'password']);
        $admin3  = User::create(['name' => 'Admin Three', 'email' => 'adm3@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'Multi Role Workspace',
            'invite_code' => 'WS-MULTI-01',
            'created_by' => $founder->id,
        ]);

        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader2->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader3->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin1->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin2->id, 'role' => 'admin', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin3->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Check Multi-Leader: 3 active leaders exist simultaneously
        $this->assertSame(3, $group->countActiveLeaders());
        $this->assertTrue($group->isLeader($founder->id));
        $this->assertTrue($group->isLeader($leader2->id));
        $this->assertTrue($group->isLeader($leader3->id));

        // Check Multi-Admin: 3 active admins exist simultaneously
        $this->assertSame('admin', $group->getMemberRole($admin1->id));
        $this->assertSame('admin', $group->getMemberRole($admin2->id));
        $this->assertSame('admin', $group->getMemberRole($admin3->id));
    }

    public function test_leader_can_promote_and_demote_members_independently(): void
    {
        // Scenarios 4, 5, 6, 7, 15: Promotions and demotions are independent
        $founder = User::create(['name' => 'Founder User', 'email' => 'founder_p@example.test', 'password' => 'password']);
        $adminCandidate = User::create(['name' => 'Admin Candidate', 'email' => 'cand@example.test', 'password' => 'password']);
        $newMember = User::create(['name' => 'New Guy', 'email' => 'newguy@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'Independent Roles WS',
            'invite_code' => 'WS-INDEP-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $adminMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $adminCandidate->id, 'role' => 'admin', 'status' => 'Aktif']);

        // 1. Promote Admin -> Leader via JSON
        $resPromote = $this->actingAs($founder)
            ->putJson(route('groups.members.update', [$group->id, $adminMember->id]), [
                'role' => 'leader',
            ]);
        $resPromote->assertOk();
        $this->assertSame('leader', $adminMember->fresh()->role);

        // Crucial: Founder is NOT demoted! Both founder and promoted user are leaders!
        $this->assertSame('leader', $group->getMemberRole($founder->id));
        $this->assertSame(2, $group->countActiveLeaders());

        // Check activity log recorded
        $this->assertDatabaseHas('spreadsheet_activities', [
            'group_id' => $group->id,
            'user_id' => $founder->id,
            'action' => 'role_changed',
            'old_value' => 'Admin',
            'new_value' => 'Leader',
        ]);

        // 2. Add a new member directly as Leader (Scenario 5)
        $this->actingAs($founder)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $newMember->id,
                'role' => 'leader',
            ])
            ->assertRedirect();
        $this->assertSame('leader', $group->getMemberRole($newMember->id));
        $this->assertSame(3, $group->countActiveLeaders());

        // 3. Promoted Leader demoted back to Admin by another Leader (Scenario 6)
        $resDemote = $this->actingAs($founder)
            ->putJson(route('groups.members.update', [$group->id, $adminMember->id]), [
                'role' => 'admin',
            ]);
        $resDemote->assertOk();
        $this->assertSame('admin', $adminMember->fresh()->role);
        $this->assertSame(2, $group->countActiveLeaders());
    }

    public function test_founder_protection_guards_against_role_change_and_removal(): void
    {
        // Scenarios 8, 9: Founder is permanently protected
        $founder = User::create(['name' => 'Original Founder', 'email' => 'orig_founder@example.test', 'password' => 'password']);
        $promotedLeader = User::create(['name' => 'Promoted Leader', 'email' => 'prom_lead@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'Protected Founder WS',
            'invite_code' => 'WS-PROT-09',
            'created_by' => $founder->id,
        ]);
        $founderMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $promotedMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $promotedLeader->id, 'role' => 'leader', 'status' => 'Aktif']);

        // Another leader attempts to demote the Founder -> 403 Forbidden
        $this->actingAs($promotedLeader)
            ->put(route('groups.members.update', [$group->id, $founderMember->id]), ['role' => 'admin'])
            ->assertForbidden();
        $this->assertSame('leader', $group->getMemberRole($founder->id));

        // Founder attempts to change own role -> 403 Forbidden
        $this->actingAs($founder)
            ->put(route('groups.members.update', [$group->id, $founderMember->id]), ['role' => 'admin'])
            ->assertForbidden();

        // Another leader attempts to delete/remove the Founder from workspace -> 403 Forbidden
        $this->actingAs($promotedLeader)
            ->delete(route('groups.members.remove', [$group->id, $founderMember->id]))
            ->assertForbidden();
        $this->assertDatabaseHas('group_members', ['id' => $founderMember->id]);
    }

    public function test_admin_cannot_change_roles_and_member_cannot_modify_own_role(): void
    {
        // Scenarios 10, 11: Admin restrictions & self-modification restriction
        $founder = User::create(['name' => 'Founder Lead', 'email' => 'founder_g@example.test', 'password' => 'password']);
        $admin1  = User::create(['name' => 'Admin Guard', 'email' => 'adm_g@example.test', 'password' => 'password']);
        $admin2  = User::create(['name' => 'Target Admin', 'email' => 'adm_t@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'Guarded WS',
            'invite_code' => 'WS-GUARD-10',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $admin1Member = GroupMember::create(['group_id' => $group->id, 'user_id' => $admin1->id, 'role' => 'admin', 'status' => 'Aktif']);
        $admin2Member = GroupMember::create(['group_id' => $group->id, 'user_id' => $admin2->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Admin cannot change another member's role
        $this->actingAs($admin1)
            ->put(route('groups.members.update', [$group->id, $admin2Member->id]), ['role' => 'leader'])
            ->assertForbidden();

        // Admin cannot elevate themselves
        $this->actingAs($admin1)
            ->put(route('groups.members.update', [$group->id, $admin1Member->id]), ['role' => 'leader'])
            ->assertForbidden();
    }

    public function test_karyawan_role_completely_eliminated_from_frontend_and_backend(): void
    {
        // Scenarios 12, 13, 14: Employee / Karyawan role is completely eliminated
        $founder = User::create(['name' => 'Founder User', 'email' => 'founder_k@example.test', 'password' => 'password']);
        $targetUser = User::create(['name' => 'Target User', 'email' => 'target_k@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'No Karyawan WS',
            'invite_code' => 'WS-NOKARY-01',
            'created_by' => $founder->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        $targetMember = GroupMember::create(['group_id' => $group->id, 'user_id' => $targetUser->id, 'role' => 'admin', 'status' => 'Aktif']);

        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();

        // Frontend check: No karyawan in options, badges, or buttons
        $response = $this->actingAs($founder)->get(route('group.workspace', $group->id));
        $response->assertOk();
        $response->assertDontSee('<option value="karyawan">', false);
        $response->assertDontSee('Hapus Role Karyawan');
        $response->assertDontSee('karyawan_role_removed');

        // Backend validation check: role 'karyawan' is rejected with validation error
        $this->actingAs($founder)
            ->putJson(route('groups.members.update', [$group->id, $targetMember->id]), [
                'role' => 'karyawan',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->actingAs($founder)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $targetUser->id,
                'role' => 'karyawan',
            ])
            ->assertSessionHasErrors('role');

        // Legacy mapping check: any historical 'karyawan' string in DB is evaluated as 'admin'
        $legacyMember = GroupMember::create(['group_id' => $group->id, 'user_id' => User::create(['name' => 'Old Legacy', 'email' => 'old_l@example.test', 'password' => 'password'])->id, 'role' => 'karyawan', 'status' => 'Aktif']);
        $this->assertSame('admin', $group->getMemberRole($legacyMember->user_id));
    }

    public function test_role_persistence_across_sessions_and_workspace_isolation(): void
    {
        // Scenarios 16, 17: Persistence across logout/login and workspace isolation
        $founder = User::create(['name' => 'Founder Multi WS', 'email' => 'founder_iso@example.test', 'password' => 'password']);
        $member = User::create(['name' => 'Dual Member', 'email' => 'dual_mem@example.test', 'password' => 'password']);

        $groupA = FisheriesGroup::create(['name' => 'Workspace A', 'invite_code' => 'WS-A-01', 'created_by' => $founder->id]);
        $groupB = FisheriesGroup::create(['name' => 'Workspace B', 'invite_code' => 'WS-B-01', 'created_by' => $founder->id]);

        GroupMember::create(['group_id' => $groupA->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $groupB->id, 'user_id' => $founder->id, 'role' => 'leader', 'status' => 'Aktif']);

        $memberA = GroupMember::create(['group_id' => $groupA->id, 'user_id' => $member->id, 'role' => 'admin', 'status' => 'Aktif']);
        $memberB = GroupMember::create(['group_id' => $groupB->id, 'user_id' => $member->id, 'role' => 'admin', 'status' => 'Aktif']);

        // Promote member to leader in Workspace A
        $this->actingAs($founder)
            ->putJson(route('groups.members.update', [$groupA->id, $memberA->id]), ['role' => 'leader'])
            ->assertOk();

        // Workspace A role changed to leader
        $this->assertSame('leader', $groupA->getMemberRole($member->id));

        // Workspace B role remains admin (isolated)
        $this->assertSame('admin', $groupB->getMemberRole($member->id));

        // Role persists after logout and re-login
        auth()->logout();
        $this->actingAs($member);
        $this->assertSame('leader', $groupA->getMemberRole($member->id));
        $this->assertSame('admin', $groupB->getMemberRole($member->id));
    }
    public function test_full_workspace_excel_backup_exports_all_sheets_with_formulas_and_styling(): void
    {
        $leader = User::create(['name' => 'Backup Leader', 'email' => 'backup_lead@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Dadhen Mandiri',
            'invite_code' => 'WS-DADHEN-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);

        // Create 3 sheets
        $sheet1 = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet1->initializeBlankGrid();
        $colA1 = $sheet1->columns()->where('letter', 'A')->first();
        $colB1 = $sheet1->columns()->where('letter', 'B')->first();
        $row1 = $sheet1->rows()->where('sort_order', 1)->first();

        // Put values & formula
        SpreadsheetCell::create([
            'row_id' => $row1->id,
            'column_id' => $colA1->id,
            'value' => '150000',
            'format' => ['bold' => true, 'bg' => '#E0F2FE', 'numFormat' => 'currency'],
        ]);
        SpreadsheetCell::create([
            'row_id' => $row1->id,
            'column_id' => $colB1->id,
            'value' => '300000',
            'formula' => '=A1*2',
            'format' => ['bold' => true, 'numFormat' => 'currency'],
        ]);

        $sheet2 = $group->sheets()->create(['name' => 'Cost', 'category' => 'cost']);
        $sheet2->initializeBlankGrid();

        $sheet3 = $group->sheets()->create(['name' => 'Rank & Summary', 'category' => 'rank']);
        $sheet3->initializeBlankGrid();

        $response = $this->actingAs($leader)
            ->get(route('groups.backup', $group->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Backup_Dadhen_Mandiri_', $response->headers->get('Content-Disposition'));

        $binaryFile = $response->getFile();
        $this->assertNotNull($binaryFile);
        $this->assertTrue(file_exists($binaryFile->getPathname()));

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $loadedWorkbook = $reader->load($binaryFile->getPathname());

        $sheetNames = $loadedWorkbook->getSheetNames();
        $this->assertCount(3, $sheetNames);
        $this->assertSame('Analisa', $sheetNames[0]);
        $this->assertSame('Cost', $sheetNames[1]);
        $this->assertSame('Rank & Summary', $sheetNames[2]);

        $analisaWorksheet = $loadedWorkbook->getSheetByName('Analisa');
        $this->assertSame('=A1*2', $analisaWorksheet->getCell('B1')->getValue());
        $this->assertTrue($analisaWorksheet->getStyle('A1')->getFont()->getBold());
    }

    public function test_karyawan_cannot_access_full_workspace_backup(): void
    {
        $leader = User::create(['name' => 'Lead', 'email' => 'lead_b@example.test', 'password' => 'password']);
        $karyawan = User::create(['name' => 'Karyawan', 'email' => 'kary_b@example.test', 'password' => 'password']);

        $group = FisheriesGroup::create([
            'name' => 'Protected Backup WS',
            'invite_code' => 'WS-PROT-01',
            'created_by' => $leader->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $leader->id, 'role' => 'leader', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $karyawan->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create(['name' => 'Analisa', 'category' => 'analisa']);
        $sheet->initializeBlankGrid();

        $outsider = User::create(['name' => 'Outsider', 'email' => 'out_add@example.test', 'password' => 'password']);
        $this->actingAs($outsider)
            ->get(route('groups.backup', $group->id))
            ->assertForbidden();
    }
}
