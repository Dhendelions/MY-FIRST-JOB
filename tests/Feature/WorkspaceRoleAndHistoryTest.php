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
                'role' => 'karyawan',
            ]);
        $addRes->assertRedirect();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $candidate1->id,
            'role' => 'karyawan',
            'status' => 'Aktif',
        ]);

        // Duplicate addition should be prevented
        $duplicateRes = $this->actingAs($leader)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $candidate1->id,
                'role' => 'karyawan',
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
        GroupMember::create(['group_id' => $group->id, 'user_id' => $karyawan->id, 'role' => 'karyawan', 'status' => 'Aktif']);

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
        $this->actingAs($karyawan)->get(route('group.workspace', [$group->id, $analisa->id]))->assertOk();
        $this->actingAs($admin)->get(route('group.workspace', [$group->id, $cost->id]))->assertOk();
        $this->actingAs($leader)->get(route('group.workspace', [$group->id, $analisa->id]))->assertOk();

        // 2. Margin, Pendukung, Rank: Admin & Karyawan are RESTRICTED
        $resKaryawanMargin = $this->actingAs($karyawan)->get(route('group.workspace', [$group->id, $margin->id]));
        $resKaryawanMargin->assertOk();
        $resKaryawanMargin->assertSee('Akses Lembar Kerja Dibatasi');
        // Ensure sensitive grid component is NOT rendered
        $resKaryawanMargin->assertDontSee('spreadsheet-grid-table');

        // Backend API check: export should be forbidden (403) for non-leader on restricted sheet
        $this->actingAs($karyawan)->get(route('spreadsheet.export', $margin))->assertForbidden();
        $this->actingAs($admin)->get(route('spreadsheet.export', $pendukung))->assertForbidden();
        $this->actingAs($karyawan)->get(route('spreadsheet.export', $rank))->assertForbidden();

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
        $this->actingAs($karyawan)
            ->post(route('groups.members.add', $group->id), [
                'user_id' => $leader->id,
                'role' => 'karyawan',
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
}
