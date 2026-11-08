<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use App\Services\CompanyWorkspaceInitializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create verified Owner
        $this->owner = User::create([
            'name' => 'Dadhen Owner',
            'email' => 'owner_dynamic@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'role_title' => 'Owner',
        ]);

        // 2. Create verified Admin
        $this->admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'admin_dynamic@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'role_title' => 'Admin',
        ]);
    }

    public function test_owner_can_create_a_new_operational_year(): void
    {
        $targetYear = 2027;

        $response = $this->actingAs($this->owner)
            ->post(route('company.years.store'), [
                'year' => $targetYear,
            ]);

        $response->assertSessionHasNoErrors();

        // 1. Operational year created and active
        $opYear = OperationalYear::where('year', $targetYear)->first();
        $this->assertNotNull($opYear);
        $this->assertTrue($opYear->is_active);
        $this->assertFalse($opYear->is_archived);

        // 2. Exactly 12 monthly workspaces created
        $monthlyWorkspaces = $opYear->monthlyWorkspaces;
        $this->assertCount(12, $monthlyWorkspaces);

        // 3. Exactly 60 spreadsheet sheets created (5 per month)
        $sheetCount = SpreadsheetSheet::whereIn('monthly_workspace_id', $monthlyWorkspaces->pluck('id'))->count();
        $this->assertEquals(60, $sheetCount);

        // 4. Verify standard sheet names per month
        $expectedNames = ['Analisa', 'Cost', 'Margin', 'Pendukung', 'Rank'];
        foreach ($monthlyWorkspaces as $mw) {
            $sheetNames = $mw->group->sheets->pluck('name')->all();
            $this->assertCount(5, $sheetNames);
            foreach ($expectedNames as $exp) {
                $this->assertContains($exp, $sheetNames);
            }
        }
    }

    public function test_admin_cannot_create_an_operational_year(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('company.years.store'), [
                'year' => 2028,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('operational_years', ['year' => 2028]);
    }

    public function test_duplicate_year_is_rejected(): void
    {
        OperationalYear::create([
            'year' => 2024,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('company.years.store'), [
                'year' => 2024,
            ]);

        $response->assertSessionHasErrors(['year']);
    }

    public function test_invalid_year_formats_are_rejected(): void
    {
        $invalidYears = [202, 20255, -2025, 'abcd', ''];

        foreach ($invalidYears as $invalid) {
            $response = $this->actingAs($this->owner)
                ->post(route('company.years.store'), [
                    'year' => $invalid,
                ]);

            $response->assertSessionHasErrors(['year']);
        }
    }

    public function test_provisioning_is_idempotent_and_safe_when_retried(): void
    {
        $initializer = app(CompanyWorkspaceInitializer::class);

        // First run
        $year2029 = $initializer->provisionYear(2029, $this->owner->id, false);
        $this->assertEquals(12, $year2029->monthlyWorkspaces()->count());
        $initialSheets = SpreadsheetSheet::whereIn('monthly_workspace_id', $year2029->monthlyWorkspaces()->pluck('id'))->count();
        $this->assertEquals(60, $initialSheets);

        // Retry run with allowRecovery = true
        $retriedYear = $initializer->provisionYear(2029, $this->owner->id, true);
        $this->assertEquals($year2029->id, $retriedYear->id);
        $this->assertEquals(12, $retriedYear->monthlyWorkspaces()->count());
        $retriedSheets = SpreadsheetSheet::whereIn('monthly_workspace_id', $retriedYear->monthlyWorkspaces()->pluck('id'))->count();
        $this->assertEquals(60, $retriedSheets);
    }

    public function test_failed_provisioning_can_be_recovered(): void
    {
        // Simulate an incomplete year with only 1 month created
        $incompleteYear = OperationalYear::create([
            'year' => 2030,
            'is_active' => false,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        $group = FisheriesGroup::create([
            'name' => 'Januari 2030',
            'category' => 'Bulanan',
            'invite_code' => 'OPS203001',
            'created_by' => $this->owner->id,
            'operational_year' => 2030,
            'operational_month' => 1,
        ]);

        MonthlyWorkspace::create([
            'year_id' => $incompleteYear->id,
            'month' => 1,
            'name' => 'Januari',
            'group_id' => $group->id,
            'status' => 'active',
        ]);

        $this->assertFalse($incompleteYear->is_active);
        $this->assertEquals(1, $incompleteYear->monthlyWorkspaces()->count());

        // Run recovery provisioning
        $recoveredYear = app(CompanyWorkspaceInitializer::class)->provisionYear(2030, $this->owner->id, true);

        $this->assertTrue($recoveredYear->fresh()->is_active);
        $this->assertEquals(12, $recoveredYear->monthlyWorkspaces()->count());
        $sheetCount = SpreadsheetSheet::whereIn('monthly_workspace_id', $recoveredYear->monthlyWorkspaces()->pluck('id'))->count();
        $this->assertEquals(60, $sheetCount);
    }

    public function test_owner_can_archive_and_restore_operational_year(): void
    {
        $year = OperationalYear::create([
            'year' => 2024,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        // 1. Archive
        $resArchive = $this->actingAs($this->owner)
            ->post(route('company.years.archive', $year->id));

        $resArchive->assertSessionHasNoErrors();
        $this->assertTrue($year->fresh()->is_archived);
        $this->assertFalse($year->fresh()->is_active);
        $this->assertEquals('archived', $year->fresh()->status);

        // 2. Restore
        $resRestore = $this->actingAs($this->owner)
            ->post(route('company.years.restore', $year->id));

        $resRestore->assertSessionHasNoErrors();
        $this->assertFalse($year->fresh()->is_archived);
        $this->assertTrue($year->fresh()->is_active);
        $this->assertEquals('active', $year->fresh()->status);
    }

    public function test_admin_cannot_archive_or_restore_operational_year(): void
    {
        $year = OperationalYear::create([
            'year' => 2024,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('company.years.archive', $year->id))
            ->assertForbidden();

        $year->archive();

        $this->actingAs($this->admin)
            ->post(route('company.years.restore', $year->id))
            ->assertForbidden();
    }

    public function test_archived_workspace_is_strictly_read_only(): void
    {
        $year = OperationalYear::create([
            'year' => 2025,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);

        $group = FisheriesGroup::create([
            'name' => 'Januari 2025',
            'category' => 'Bulanan',
            'invite_code' => 'OPS202501',
            'created_by' => $this->owner->id,
            'operational_year' => 2025,
            'operational_month' => 1,
        ]);

        $mw = MonthlyWorkspace::create([
            'year_id' => $year->id,
            'month' => 1,
            'name' => 'Januari',
            'group_id' => $group->id,
            'status' => 'active',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'status' => 'Aktif',
            'joined_at' => now(),
        ]);

        $sheet = $group->sheets()->create([
            'name' => 'Analisa',
            'category' => 'analisa',
            'monthly_workspace_id' => $mw->id,
        ]);

        $sheet->initializeBlankGrid();

        $row = $sheet->rows()->firstOrFail();
        $col = $sheet->columns()->firstOrFail();

        // 1. When year is active, saving a cell is allowed
        $resActive = $this->actingAs($this->owner)
            ->postJson(route('spreadsheet.cell.save'), [
                'row_id' => $row->id,
                'column_id' => $col->id,
                'value' => 'Nilai Uji Coba',
            ]);
        $resActive->assertOk();

        // 2. Archive the year
        $year->archive();

        // 3. When archived, saving cell must return 403 Forbidden
        $resArchived = $this->actingAs($this->owner)
            ->postJson(route('spreadsheet.cell.save'), [
                'row_id' => $row->id,
                'column_id' => $col->id,
                'value' => 'Nilai Gagal Saat Arsip',
            ]);
        $resArchived->assertForbidden();

        // 4. Other mutations (add column, add row) must also return 403 Forbidden
        $this->actingAs($this->owner)
            ->postJson(route('spreadsheet.column.add', $sheet->id))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->postJson(route('spreadsheet.row.add', $sheet->id))
            ->assertForbidden();

        // 5. Read actions (export CSV) remain functional
        $this->actingAs($this->owner)
            ->get(route('spreadsheet.export', $sheet->id))
            ->assertOk();

        // 6. Restoring the year re-enables mutations
        $year->restore();

        $resRestored = $this->actingAs($this->owner)
            ->postJson(route('spreadsheet.cell.save'), [
                'row_id' => $row->id,
                'column_id' => $col->id,
                'value' => 'Nilai Berhasil Pasca Pulih',
            ]);
        $resRestored->assertOk();
    }

    public function test_admin_cannot_access_archived_workspace_routes_or_data_but_owner_can_restore(): void
    {
        $year = OperationalYear::create([
            'year' => 2032,
            'is_active' => true,
            'is_archived' => false,
            'created_by' => $this->owner->id,
        ]);
        $group = FisheriesGroup::create([
            'name' => 'Januari 2032',
            'category' => 'Bulanan',
            'invite_code' => 'OPS203201',
            'created_by' => $this->owner->id,
            'operational_year' => 2032,
            'operational_month' => 1,
        ]);
        $workspace = MonthlyWorkspace::create([
            'year_id' => $year->id,
            'month' => 1,
            'name' => 'Januari',
            'group_id' => $group->id,
            'status' => 'active',
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $this->owner->id, 'role' => 'owner', 'status' => 'Aktif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $this->admin->id, 'role' => 'admin', 'status' => 'Aktif']);
        $sheet = $group->sheets()->create([
            'name' => 'Analisa',
            'category' => 'analisa',
            'monthly_workspace_id' => $workspace->id,
        ]);
        $sheet->initializeBlankGrid();
        $year->archive();

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('🔒 Diarsipkan')
            ->assertSee('opacity-60 cursor-not-allowed');

        $this->actingAs($this->admin)->get(route('company.year', 2032))->assertForbidden();
        $this->actingAs($this->admin)->get(route('company.workspace', [2032, 1]))->assertForbidden();
        $this->actingAs($this->admin)->get(route('group.workspace', $group))->assertForbidden();
        $this->actingAs($this->admin)->get(route('spreadsheet.export', $sheet))->assertForbidden();
        $this->actingAs($this->admin)->post(route('groups.unlock', $group), [
            'password' => 'any-value',
        ])->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('group.workspace', $group))
            ->assertOk();
        $this->actingAs($this->owner)
            ->get(route('spreadsheet.export', $sheet))
            ->assertOk();
        $this->actingAs($this->owner)
            ->post(route('company.years.restore', $year->id))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->get(route('group.workspace', $group))
            ->assertOk();
    }

    public function test_analytics_and_yearly_comparison_dynamically_incorporate_new_years(): void
    {
        $year2024 = OperationalYear::create(['year' => 2024, 'is_active' => true, 'is_archived' => false]);
        $year2025 = OperationalYear::create(['year' => 2025, 'is_active' => true, 'is_archived' => false]);

        $resAnalytics = $this->actingAs($this->owner)->get(route('company.analytics'));
        $resAnalytics->assertOk();
        $resAnalytics->assertSee('2024');
        $resAnalytics->assertSee('2025');

        // Dynamically add 2031
        OperationalYear::create(['year' => 2031, 'is_active' => true, 'is_archived' => false]);

        // Check Analytics recognizes 2031 dynamically
        $resAnalyticsAfter = $this->actingAs($this->owner)->get(route('company.analytics', ['year' => 2031]));
        $resAnalyticsAfter->assertOk();
        $resAnalyticsAfter->assertSee('2031');

        // Check Yearly Comparison recognizes 2031
        $resComparison = $this->actingAs($this->owner)->get(route('company.yearly-comparison'));
        $resComparison->assertOk();
        $resComparison->assertSee('2031');
    }
}
