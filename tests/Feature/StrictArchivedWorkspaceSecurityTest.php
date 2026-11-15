<?php

namespace Tests\Feature;

use App\Livewire\SpreadsheetWorksheet;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StrictArchivedWorkspaceSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private OperationalYear $archivedYear;
    private OperationalYear $activeYear;
    private FisheriesGroup $archivedGroup;
    private SpreadsheetSheet $archivedSheet;
    private FisheriesGroup $activeGroup;
    private SpreadsheetSheet $activeSheet;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Owner & Admin
        $this->owner = User::create([
            'name' => 'Dadhen Owner',
            'email' => 'owner@dadhenmandiri.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Yudimade',
            'email' => 'yudimade979@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);

        // 2. Archived Year & Workspace Setup
        $this->archivedYear = OperationalYear::create([
            'year' => 2024,
            'is_active' => true,
            'is_archived' => true,
        ]);

        $this->archivedGroup = FisheriesGroup::create([
            'name' => 'Januari 2024',
            'invite_code' => 'INV-2024-01',
            'created_by' => $this->owner->id,
            'operational_year' => 2024,
            'operational_month' => 1,
        ]);

        $archivedMonth = MonthlyWorkspace::create([
            'year_id' => $this->archivedYear->id,
            'group_id' => $this->archivedGroup->id,
            'month' => 1,
            'name' => 'Januari',
        ]);

        $this->archivedSheet = SpreadsheetSheet::create([
            'group_id' => $this->archivedGroup->id,
            'monthly_workspace_id' => $archivedMonth->id,
            'name' => 'Analisa',
            'order' => 1,
            'type' => 'operational',
        ]);

        GroupMember::create([
            'group_id' => $this->archivedGroup->id,
            'user_id' => $this->admin->id,
            'role' => 'editor',
            'status' => 'Aktif',
        ]);

        GroupMember::create([
            'group_id' => $this->archivedGroup->id,
            'user_id' => $this->owner->id,
            'role' => 'leader',
            'status' => 'Aktif',
        ]);

        // 3. Active Year & Workspace Setup
        $this->activeYear = OperationalYear::create([
            'year' => 2025,
            'is_active' => true,
            'is_archived' => false,
        ]);

        $this->activeGroup = FisheriesGroup::create([
            'name' => 'Januari 2025',
            'invite_code' => 'INV-2025-01',
            'created_by' => $this->owner->id,
            'operational_year' => 2025,
            'operational_month' => 1,
        ]);

        $activeMonth = MonthlyWorkspace::create([
            'year_id' => $this->activeYear->id,
            'group_id' => $this->activeGroup->id,
            'month' => 1,
            'name' => 'Januari',
        ]);

        $this->activeSheet = SpreadsheetSheet::create([
            'group_id' => $this->activeGroup->id,
            'monthly_workspace_id' => $activeMonth->id,
            'name' => 'Analisa',
            'order' => 1,
            'type' => 'operational',
        ]);

        GroupMember::create([
            'group_id' => $this->activeGroup->id,
            'user_id' => $this->admin->id,
            'role' => 'editor',
            'status' => 'Aktif',
        ]);

        GroupMember::create([
            'group_id' => $this->activeGroup->id,
            'user_id' => $this->owner->id,
            'role' => 'leader',
            'status' => 'Aktif',
        ]);
    }

    public function test_admin_cannot_access_archived_operational_year_view_directly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('company.year', $this->archivedYear->year));

        $response->assertForbidden();
    }

    public function test_admin_cannot_unlock_archived_operational_year(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('company.year.unlock', $this->archivedYear->year), [
                'password' => 'secret123',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_archived_monthly_workspace_via_group_route(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('group.workspace', $this->archivedGroup->id));

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_archived_workspace_via_company_workspace_route(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('company.workspace', [
                'year' => $this->archivedYear->year,
                'month' => 1,
                'sheetName' => 'Analisa',
            ]));

        $response->assertForbidden();
    }

    public function test_admin_cannot_unlock_archived_group(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('groups.unlock', $this->archivedGroup->id), [
                'password' => 'secret123',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_export_archived_sheet_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('spreadsheet.export', $this->archivedSheet->id));

        $response->assertForbidden();
    }

    public function test_admin_cannot_download_archived_workspace_backup_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('groups.backup', $this->archivedGroup->id));

        $response->assertForbidden();
    }

    public function test_owner_can_access_archived_operational_year_and_workspace(): void
    {
        $yearResponse = $this->actingAs($this->owner)
            ->get(route('company.year', $this->archivedYear->year));

        $yearResponse->assertOk();

        $workspaceResponse = $this->actingAs($this->owner)
            ->get(route('company.workspace', [
                'year' => $this->archivedYear->year,
                'month' => 1,
            ]));

        $workspaceResponse->assertOk();
    }

    public function test_admin_can_access_active_operational_year(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('company.year', $this->activeYear->year));

        $response->assertOk();
    }

    public function test_livewire_spreadsheet_terminates_and_redirects_if_workspace_is_archived(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->archivedSheet])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }
}
