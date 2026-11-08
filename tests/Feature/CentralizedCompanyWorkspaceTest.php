<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use App\Services\CompanyWorkspaceInitializer;
use App\Services\SpreadsheetWorkbookCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralizedCompanyWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_years_exist_and_enforce_uniqueness(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $this->actingAs($user);

        $initializer = app(CompanyWorkspaceInitializer::class);
        $result = $initializer->initialize();

        $this->assertSame(3, OperationalYear::count());
        $this->assertTrue(OperationalYear::where('year', 2024)->exists());
        $this->assertTrue(OperationalYear::where('year', 2025)->exists());
        $this->assertTrue(OperationalYear::where('year', 2026)->exists());

        // Attempting to create duplicate year must throw exception
        $this->expectException(\Illuminate\Database\QueryException::class);
        OperationalYear::create(['year' => 2024]);
    }

    public function test_every_year_has_exactly_twelve_months_and_enforces_uniqueness(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $this->actingAs($user);

        app(CompanyWorkspaceInitializer::class)->initialize();

        $years = OperationalYear::with('monthlyWorkspaces')->get();
        $this->assertCount(3, $years);

        foreach ($years as $year) {
            $this->assertCount(12, $year->monthlyWorkspaces, "Year {$year->year} must have exactly 12 monthly workspaces.");
            $months = $year->monthlyWorkspaces->pluck('month')->sort()->values()->all();
            $this->assertSame(range(1, 12), $months, "Year {$year->year} months must range from 1 to 12.");
        }

        $this->assertSame(36, MonthlyWorkspace::count());

        // Attempting to create duplicate month for same year must throw exception
        $year2024 = OperationalYear::where('year', 2024)->first();
        $dummyGroup = FisheriesGroup::create(['name' => 'Dummy Group', 'invite_code' => 'DUMMY-01', 'created_by' => $user->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        MonthlyWorkspace::create([
            'year_id' => $year2024->id,
            'month' => 1,
            'name' => 'Januari Duplicate',
            'group_id' => $dummyGroup->id,
        ]);
    }

    public function test_every_month_has_exactly_five_spreadsheets_with_standard_names(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $this->actingAs($user);

        app(CompanyWorkspaceInitializer::class)->initialize();

        $monthlyWorkspaces = MonthlyWorkspace::with('sheets')->get();
        $this->assertCount(36, $monthlyWorkspaces);

        $expectedNames = ['Analisa', 'Cost', 'Margin', 'Pendukung', 'Rank'];
        sort($expectedNames);

        foreach ($monthlyWorkspaces as $mw) {
            $this->assertCount(5, $mw->sheets, "Monthly workspace {$mw->id} ({$mw->formatted_period}) must have exactly 5 sheets.");
            $sheetNames = $mw->sheets->pluck('name')->sort()->values()->all();
            $this->assertSame($expectedNames, $sheetNames, "Sheet names for {$mw->formatted_period} must match canonical names.");
        }

        $this->assertSame(180, SpreadsheetSheet::whereNotNull('monthly_workspace_id')->count());
    }

    public function test_spreadsheet_data_is_correctly_associated_with_month_and_year(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $this->actingAs($user);

        app(CompanyWorkspaceInitializer::class)->initialize();

        $mwOct2026 = MonthlyWorkspace::whereHas('year', fn ($q) => $q->where('year', 2026))
            ->where('month', 10)
            ->firstOrFail();

        $this->assertSame('Oktober', $mwOct2026->name);
        $this->assertSame('Oktober 2026', $mwOct2026->formatted_period);

        $analisaSheet = $mwOct2026->sheets()->where('name', 'Analisa')->firstOrFail();
        $this->assertSame($mwOct2026->id, $analisaSheet->monthly_workspace_id);
        $this->assertSame($mwOct2026->group_id, $analisaSheet->group_id);
        $this->assertSame($mwOct2026->year_id, $mwOct2026->year->id);

        // Verify sheet has columns and rows materialized from template
        $this->assertGreaterThanOrEqual(20, $analisaSheet->columns()->count());
        $this->assertGreaterThanOrEqual(28, $analisaSheet->rows()->count());
    }

    public function test_existing_historical_data_remains_intact_and_marked_legacy(): void
    {
        $user = User::create(['name' => 'Legacy Creator', 'email' => 'legacy@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $legacyGroup = FisheriesGroup::create([
            'name' => 'Historical Pond Group 2023',
            'invite_code' => 'HIST-2023-01',
            'created_by' => $user->id,
            'is_legacy' => false,
        ]);
        GroupMember::create(['group_id' => $legacyGroup->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $legacySheet = $legacyGroup->sheets()->create(['name' => 'Analisa Historis', 'category' => 'custom']);
        $col = $legacySheet->columns()->create(['letter' => 'A', 'title' => '', 'width' => 120, 'sort_order' => 1]);
        $row = $legacySheet->rows()->create(['sort_order' => 1]);
        $cell = SpreadsheetCell::create([
            'row_id' => $row->id,
            'column_id' => $col->id,
            'value' => 'Data Historis 99999',
            'version' => 1,
        ]);

        $this->actingAs($user);
        $initializer = app(CompanyWorkspaceInitializer::class);
        $result = $initializer->initialize();

        // Verify legacy group is marked as legacy and not deleted
        $legacyGroup->refresh();
        $this->assertTrue($legacyGroup->is_legacy);
        $this->assertNull($legacyGroup->operational_year);

        // Verify legacy cell value remains completely intact
        $this->assertDatabaseHas('spreadsheet_cells', [
            'id' => $cell->id,
            'value' => 'Data Historis 99999',
        ]);
    }

    public function test_no_duplicate_workspaces_are_generated_on_repeated_runs(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $this->actingAs($user);

        $initializer = app(CompanyWorkspaceInitializer::class);

        // Run 1
        $res1 = $initializer->initialize();
        $this->assertSame(3, $res1['years_created']);
        $this->assertSame(36, $res1['workspaces_created']);
        $this->assertSame(180, $res1['sheets_created']);

        // Run 2 (Idempotency check)
        $res2 = $initializer->initialize();
        $this->assertSame(0, $res2['years_created']);
        $this->assertSame(0, $res2['workspaces_created']);
        $this->assertSame(0, $res2['sheets_created']);

        $this->assertSame(3, OperationalYear::count());
        $this->assertSame(36, MonthlyWorkspace::count());
        $this->assertSame(180, SpreadsheetSheet::whereNotNull('monthly_workspace_id')->count());
    }

    public function test_company_workspace_route_resolves_and_renders_hierarchy(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password', 'role_title' => 'leader']);
        $this->actingAs($user);

        app(CompanyWorkspaceInitializer::class)->initialize();

        $response = $this->get(route('company.workspace', [2026, 10, 'Analisa']));
        $response->assertOk();
        $response->assertSee('Tahun 2026');
        $response->assertSee('Oktober');
        $response->assertSee('PT Dadhen Mandiri');
    }
}

